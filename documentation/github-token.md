# GitHub token

You need a github token to access all GitHub repos, both private and public. 

## Create a token

Go to [GitHub tokens](https://github.com/settings/tokens) and register a new personal token.
You need to mark "repo". Set the expiration date. Set a title. Copy the token from the top of the page.

Document your token.

Token setup (fine-grained PAT):
- Name: Any name you want
- Resource owner: Pick one
- Expiration: custom, 2026-12-31, or the latest date the org allows
- Repository access: only the repositories you select
- Organisation and account permissions: none needed

Repository permissions:

┌─────────────────┬────────────────┬───────────────────────────────────────────────────────────────────────┐
│   Permission    │     Level      │                                  Why                                  │
├─────────────────┼────────────────┼───────────────────────────────────────────────────────────────────────┤
│ Metadata        │ Read           │ Required, GitHub sets it automatically                                │
├─────────────────┼────────────────┼───────────────────────────────────────────────────────────────────────┤
│ Contents        │ Read and write │ Push branches, merge PRs, delete branches                             │
├─────────────────┼────────────────┼───────────────────────────────────────────────────────────────────────┤
│ Pull requests   │ Read and write │ Create, comment, review, request reviewers (including CoPilot), merge │
├─────────────────┼────────────────┼───────────────────────────────────────────────────────────────────────┤
│ Issues          │ Read and write │ Comments in a PR's main conversation go through the Issues API        │
├─────────────────┼────────────────┼───────────────────────────────────────────────────────────────────────┤
│ Commit statuses │ Read           │ See whether CI passed                                                 │
├─────────────────┼────────────────┼───────────────────────────────────────────────────────────────────────┤
│ Actions         │ Read           │ See CI runs and read their logs                                       │
├─────────────────┼────────────────┼───────────────────────────────────────────────────────────────────────┤
│ Workflows       │ Read and write │ Only if Claude should push changes to .github/workflows/*             │
└─────────────────┴────────────────┴───────────────────────────────────────────────────────────────────────┘

Notes from the task:
- Fine-grained PATs have no "Checks" permission. Actions: Read covers reading CI results.
- To request a CoPilot review, the token only needs Pull requests: write. Your account also needs a Copilot seat that includes code review.
- If the org requires approval, the token stays pending, and returns 401/403, until an admin approves it.

## Token in ROX before installation

Search in the rox-folder for ENV GITHUB_TOKEN and set your token here.
``` 
ENV GITHUB_TOKEN ghp_xxxxxxxxxxxxxxxxxxxxx
```

## Token in ROX after installation
You can set a new token in an existing installation.
```
rox shell app root
sudo -u "dockerhost" composer config --global github-oauth.github.com "ghp_xxxxxxxxxxxxxxxxxxxxx"  
```