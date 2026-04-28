# Scripts

## Wiki synchronization

`sync-wiki.sh` publishes repository documentation to GitHub and GitLab wiki
repositories. The source of truth stays in this repository:

- `README.md`
- `docs/*.md`
- `frontend/README.md`
- `docs/assets/*`

Generate or update the local wiki checkout:

```bash
scripts/sync-wiki.sh
```

Generate, commit and push wiki pages to both remotes:

```bash
PUSH=1 scripts/sync-wiki.sh
```

Defaults:

- GitHub wiki: `git@github.com:robotateme/mqtt-project.wiki.git`
- GitLab wiki: `git@gitlab.com:robotateme/mqtt-project.wiki.git`
- local checkout: `.wiki`

Override them with `GITHUB_WIKI_URL`, `GITLAB_WIKI_URL` and `WIKI_DIR`.
