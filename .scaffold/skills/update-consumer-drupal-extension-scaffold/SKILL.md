---
name: update-consumer-drupal-extension-scaffold
description: Hand over to update-consumer-eddy, which updates a Drupal extension project to the latest version of Eddy
user-invocable: true
---

# Hand over to the Eddy update skill

This project's scaffold is [Eddy](https://github.com/drevops/eddy), and its
update skill is `update-consumer-eddy`. This skill only installs that one,
removes itself and hands over. Run every command below as its own Bash call.

## Step 1: Fetch the Eddy update skill

```bash
mkdir -p .claude/skills/update-consumer-eddy
```

```bash
curl -fsSL https://raw.githubusercontent.com/drevops/eddy/1.x/.eddy/skills/update-consumer-eddy/SKILL.md -o .claude/skills/update-consumer-eddy/SKILL.md
```

Read `.claude/skills/update-consumer-eddy/SKILL.md` and confirm its frontmatter
declares `name: update-consumer-eddy`. If the download failed or the file holds
anything else, stop here and tell the user. Leave this skill in place, so the
next attempt starts over from Step 1.

## Step 2: Remove this skill

```bash
rm -rf .claude/skills/update-consumer-drupal-extension-scaffold
```

Eddy's `.gitignore` ignores only `.claude/skills/update-consumer-eddy/`, so
this directory would otherwise be committed with the update.

## Step 3: Run the Eddy update skill

Invoke the `update-consumer-eddy` skill and follow all of its steps, passing on
the version the user named, if any. If the skill isn't listed yet, read
`.claude/skills/update-consumer-eddy/SKILL.md` and follow it instead.

The update replaces `AGENTS.md` with Eddy's version, whose "Updating the
scaffold" section fetches `update-consumer-eddy` directly. Before the update
commits, confirm that `AGENTS.md` no longer mentions
`update-consumer-drupal-extension-scaffold`. If it still does, point that
section at `update-consumer-eddy` and at
`https://raw.githubusercontent.com/drevops/eddy/1.x/.eddy/skills/update-consumer-eddy/SKILL.md`.
