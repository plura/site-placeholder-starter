# Deploying

**The standard is cPanel Git Version Control, auto-deploying on merge to `main`.** SFTP is the
fallback for hosts that can't do it, and stays configured alongside either way — see
[Both at once](#both-at-once).

So the only question per project is whether the host allows the standard, and that is the
host's answer, not a preference:

| | SFTP sync | cPanel Git Version Control |
| --- | --- | --- |
| Who copies | your editor | the server |
| Config lives in | `.vscode/sftp.json` (gitignored) | `.cpanel.yml` (committed) |
| Needs | FTP/SFTP credentials | cPanel with **Git™ Version Control** under Files |
| Can delete removed files | yes, with `syncOption.delete` | **no, never** |
| What's live is | whatever was last uploaded | a named commit |

**Check the host first.** Log into cPanel and look for Git™ Version Control under Files. If it
isn't there, the question is settled: SFTP.

Why it's the standard: the live site becomes a commit you can name, deploys are reproducible
and logged, rollback is `git revert` plus a re-run, and write credentials live in a secret store
instead of on a laptop. Its one real regression is that `cp -R` cannot delete.

**A project's own README should not re-explain any of this** — it belongs here, once. Record
only what deviates: a host quirk, a trigger that isn't auto-on-push, or setup that isn't
finished yet. See "What a project's README should say" in [installations.md](installations.md).

## SFTP sync

Ships ready: `cp .vscode/sftp.json.example .vscode/sftp.json` and fill in host and credentials.
The `context` is `placeholder`, so the local `placeholder/` maps to the remote `/public_html/`.
Keep `syncOption.delete` at `false` unless you are certain nothing lives on the server that
isn't in the repo — `starter/app/config.php` always does.

## cPanel Git Version Control

Two example files, both **at this repo's root**:

- `.cpanel.yml.example` — the include-list the server runs after pulling.
- `.github/workflows/deploy.yml.example` — triggers that pull over HTTPS.

**These two are the only files here that move UP a level on install.** Everything else in this
repo becomes the project's `placeholder/`; these go to the *repo* root beside `README.md`,
because cPanel reads `.cpanel.yml` only from there and GitHub reads workflows only from
`<repo-root>/.github/workflows/`. A copy left inside `placeholder/` does nothing, silently.

Setup, once:

1. Copy both up, drop the `.example` suffixes, fill in the cPanel username, home path and
   repo name.
2. In cPanel → Git™ Version Control, clone the repo to `~/repositories/<repo-name>` —
   **outside `public_html`**, or `.git/`, the README and `mail-templates/` become publicly
   reachable.
3. Clone over **HTTPS**, never SSH. A public repo needs no auth; a private one takes a
   fine-grained read-only PAT in the URL. Don't attempt SSH deploy keys — cPanel's key-gen UI
   forces a passphrase, custom key filenames silently go unused, and ed25519 keys hit "invalid
   format" from cPanel's bundled git. Shared hosts also frequently firewall SSH outright even
   while offering a key-management page (`ssh -v -o ConnectTimeout=8 user@host` — a timeout
   means no shell).
4. cPanel → Security → Manage API Tokens, generate one, store it as a GitHub repo secret named
   `CPANEL_TOKEN`.
5. Upload `starter/app/config.php` over SFTP. It is gitignored, so no deploy will ever create
   or update it.

### The two things that bite

**`cp -R` cannot delete.** Removing a file from the repo does not remove it from the webroot —
not on the next deploy, not ever. Restructuring leaves the old tree live alongside the new one,
still serving, still holding whatever credentials it held. Plan the manual cleanup as part of
any move, and do it *after* verifying, since the old tree is what makes rollback cheap.

**Push and deploy are separate decisions, until you decide otherwise.** The shipped workflow
auto-deploys on merge to `main` and also exposes a manual button. That is right when work
happens on branches and merging means shipping. If you commit directly to `main` while
iterating, delete the `push:` block — otherwise every half-finished commit is live.

### Both at once

A project can keep SFTP alongside a git deploy, and usually should: `starter/app/config.php` is
untracked, so a git deploy can never carry it. Point the SFTP context at `placeholder` and use
it for that one file.
