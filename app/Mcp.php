<?php

declare(strict_types=1);

namespace Dilemma;

final class Mcp
{
	public const MODERN = '2026-07-28';
	public const LEGACY = '2025-11-25';
	public const LEGACY_VERSIONS = [self::LEGACY, '2025-06-18', '2025-03-26'];
	public const SUPPORTED = [self::MODERN, ...self::LEGACY_VERSIONS];
	public const INSTRUCTIONS = AiGuide::RULES . ' Use dilemma_get_instructions for current guidance and scoped debate context. The dilemma_debate MCP prompt is also available for apps supporting prompts.';

	private static function instructions(array $actor): string
	{
		// Only server-controlled identifiers enter instructions; display names are untrusted content.
		$identity = array_intersect_key(Connections::identity($actor), array_flip(['user_id', 'connection_id', 'debate_id', 'scopes']));
		return 'CONNECTION IDENTITY: ' . Support::json($identity) . "\nCompare debate_id, user_id and connection_id with the IDs provided in the human setup before proceeding. If any supplied ID differs, stop this connection.\nSTARTUP: Call tools/list for the schemas, then dilemma_whoami and compare identity again. Verify dilemma_test_connection read_verified=true, then dilemma_test_write write_verified=true with a fresh random UUID as idempotency_key. This persists only a harmless receipt. Then get_debate, read full relevant arguments and follow workflow. HTTP 200 alone is insufficient: stop on JSON-RPC error, isError=true or 401/403/429, without repeated authentication attempts. Use these same authenticated MCP calls in later turns. Keep credentials in memory and out of tool output.\n\n" . self::INSTRUCTIONS;
	}

	public static function handle(string $method, array $headers, string $body): array
	{
		$headers = array_change_key_case($headers, CASE_LOWER);
		if ($method !== 'POST') return ['status' => 405, 'body' => null, 'headers' => ['Allow' => 'POST']];
		if (isset($headers['origin']) && $headers['origin'] !== Config::url()) return self::error(null, -32000, 'Invalid Origin.', 403);
		if (!str_starts_with(strtolower($headers['content-type'] ?? ''), 'application/json')) return self::error(null, -32600, 'Use application/json.', 415);
		if (strlen($body) > 196608) return self::error(null, -32600, 'Request too large.', 413);
		try { $request = json_decode($body, false, 32, JSON_THROW_ON_ERROR); }
		catch (\JsonException) { return self::error(null, -32700, 'Invalid JSON.', 400); }
		if (!is_object($request) || ($request->jsonrpc ?? '') !== '2.0' || !is_string($request->method ?? null)) return self::error(null, -32600, 'Invalid request.', 400);
		$id = $request->id ?? null;
		if (property_exists($request, 'id') && !is_string($id) && !is_int($id)) return self::error(null, -32600, 'Invalid request id.', 400);
		$params = $request->params ?? (object) [];
		if (!is_object($params)) return self::error($id, -32602, 'Params must be an object.', 400);
		$version = $headers['mcp-protocol-version'] ?? '2025-03-26';
		if ($request->method === 'initialize') {
			if (!property_exists($request, 'id') || !is_string($params->protocolVersion ?? null)) return self::error($id, -32602, 'Initialize requires a request id and protocolVersion.', 400);
			$version = in_array($params->protocolVersion, self::LEGACY_VERSIONS, true) ? $params->protocolVersion : self::LEGACY;
		} elseif (!isset($headers['mcp-protocol-version']) && isset($params->_meta->{'io.modelcontextprotocol/protocolVersion'})) {
			return self::error($id, -32020, 'MCP-Protocol-Version is required.', 400);
		}
		if ($request->method === 'server/discover' && !isset($headers['mcp-protocol-version'])) return self::error($id, -32020, 'MCP-Protocol-Version is required.', 400);
		if (!in_array($version, self::SUPPORTED, true)) return self::error($id, -32022, 'Unsupported protocol version.', 400, ['supported' => self::SUPPORTED, 'requested' => $version]);
		$modern = $version === self::MODERN;
		if ($modern) {
			$meta = $params->_meta ?? null;
			if (!is_object($meta) || !is_string($meta->{'io.modelcontextprotocol/protocolVersion'} ?? null) || !is_object($meta->{'io.modelcontextprotocol/clientCapabilities'} ?? null)) return self::error($id, -32602, 'Required protocol metadata is missing.', 400);
			if ($meta->{'io.modelcontextprotocol/protocolVersion'} !== $version || ($headers['mcp-method'] ?? '') !== $request->method) return self::error($id, -32020, 'Protocol headers do not match the body.', 400);
			if (in_array($request->method, ['tools/call', 'prompts/get'], true)) {
				$name = $headers['mcp-name'] ?? '';
				if (str_starts_with($name, '=?')) {
					if (!preg_match('/^=\?base64\?([A-Za-z0-9+\/]*={0,2})\?=$/D', $name, $match)) return self::error($id, -32020, 'Invalid Mcp-Name encoding.', 400);
					$decoded = base64_decode($match[1], true);
					if ($decoded === false || base64_encode($decoded) !== $match[1] || !mb_check_encoding($decoded, 'UTF-8')) return self::error($id, -32020, 'Invalid Mcp-Name encoding.', 400);
					$name = $decoded;
				}
				if ($name === '' || $name !== ($params->name ?? null)) return self::error($id, -32020, 'Mcp-Name does not match the tool name.', 400);
			}
		}
		try { $actor = Connections::authenticate($headers['authorization'] ?? ''); }
		catch (Failure $error) {
			$response = self::error($id, -32001, $error->error, $error->status);
			if ($error->status === 401) $response['headers']['WWW-Authenticate'] = 'Bearer resource_metadata="' . Config::url('/.well-known/oauth-protected-resource') . '", scope="debates:read arguments:write"';
			return $response;
		}
		if (!property_exists($request, 'id')) {
			if (str_starts_with($request->method, 'notifications/')) return ['status' => 202, 'body' => null, 'headers' => []];
			return self::error(null, -32600, 'A request id is required.', 400);
		}
		$base = $modern ? ['resultType' => 'complete'] : [];
		try {
			$result = match ($request->method) {
				'initialize' => ['protocolVersion' => $version, 'capabilities' => ['tools' => (object) [], 'prompts' => (object) []], 'serverInfo' => ['name' => 'Dilemma', 'version' => '0.1.0'], 'instructions' => self::instructions($actor)],
				'server/discover' => $modern ? $base + ['supportedVersions' => self::SUPPORTED, 'capabilities' => ['tools' => (object) [], 'prompts' => (object) []], '_meta' => ['io.modelcontextprotocol/serverInfo' => ['name' => 'Dilemma', 'version' => '0.1.0']], 'instructions' => self::instructions($actor), 'ttlMs' => 0, 'cacheScope' => 'private'] : null,
				'ping' => $modern ? $base : (object) [],
				'tools/list' => $base + ['tools' => ToolRegistry::tools($actor)],
				'prompts/list' => $base + ['prompts' => AiGuide::prompts()],
				'prompts/get' => $base + AiGuide::prompt($actor, $params),
				'tools/call' => self::call($actor, $params, $base),
				default => null,
			};
			if ($result === null) return self::error($id, -32601, 'Method not found.', $modern ? 404 : 200);
			return ['status' => 200, 'body' => ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result], 'headers' => []];
		} catch (Failure $error) {
			return self::error($id, -32602, $error->error, 400, $error->details);
		}
	}

	private static function call(array $actor, object $params, array $base): array
	{
		if (!is_string($params->name ?? null) || !is_object($params->arguments ?? (object) [])) throw new Failure(400, 'invalid_arguments');
		try {
			$result = ToolRegistry::invoke($actor, $params->name, $params->arguments ?? (object) []);
			return $base + ['isError' => false, 'structuredContent' => (object) $result, 'content' => [['type' => 'text', 'text' => Support::json((object) $result)]]];
		} catch (Failure $error) {
			return $base + ['isError' => true, 'content' => [['type' => 'text', 'text' => Support::json(['error' => $error->error, 'status' => $error->status, 'details' => (object) $error->details])]]];
		}
	}

	private static function error(mixed $id, int $code, string $message, int $status, array $data = []): array
	{
		$error = ['code' => $code, 'message' => $message];
		if ($data) $error['data'] = $data;
		return ['status' => $status, 'body' => ['jsonrpc' => '2.0', 'id' => $id, 'error' => $error], 'headers' => []];
	}
}
