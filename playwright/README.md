# WordPress Plugins Playwright E2E Repository
This repository contains end-to-end test cases written primarily for fintech plugins managed by Cynder.

## Branching Strategy
We are employing a [GitHub Flow](https://docs.github.com/en/get-started/quickstart/github-flow) style as branching strategy for this repository.
1. For any code changes, create a new branch from `main` using the JIRA ticket number as the branch name (ex. EPP-13).
2. After making the necessary changes, push the branch to GitHub and open a Pull Request with `main` as the base branch.
3. After code review and approval, use `Squash and Merge` option to close the PR. Delete the branch after merging to `main`.
