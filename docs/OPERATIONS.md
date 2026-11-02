# Development and operations

## Startup and handoff

Read AGENTS.md. Verify Drive is fully synchronized and this is the only writer;
ask the owner if unverifiable. Acquire/read back PROJECT_LOCK.md before mutations.
Never take another session's lock based on age. Recheck ownership before edits.

On this Windows machine use:

```powershell
git --git-dir=C:/Users/hello/AppData/Local/dilemma-git/dilemma.git --work-tree="G:/Min enhet/02 Projekt/Dilemma/Dev26" fetch origin
git --git-dir=C:/Users/hello/AppData/Local/dilemma-git/dilemma.git --work-tree="G:/Min enhet/02 Projekt/Dilemma/Dev26" status --short
```

Metadata lives in a normal, non-reparse local AppData directory outside Drive.
There is no `.git` directory/pointer in the shared tree. Each machine needs its own
metadata directory. Compare working bytes, local history and fetched remote history
before reconciliation. Back up unpublished content; never blindly checkout/reset.

At handoff document exact checks, revision and unpublished work. Stop project
writers, verify Drive synchronization, and release only this session's own lock.

## Private configuration

`.env*` is ignored except `.env.example`. Private key `.env.ssh_key` must remain
outside the public root. The owner's `../Dokument/Uppgifter.txt` records private
operational information independently of runtime configuration. Never read its
secret values into chat, logs or tracked documentation.

Only public SSH key material is imported into cPanel. Confirm authorization and
host identity before using it. Account access may span other websites: constrain
all remote operations to Dilemma's verified application root and dedicated DB.

## Publication

Do not publish until authorized. Before any release inspect staged files and
history for secrets, validate tests, update docs and create a coherent commit.
Deploy only the same verified revision, excluding local environment/private files.
Never overwrite another site's document root or run global cPanel modifications.
