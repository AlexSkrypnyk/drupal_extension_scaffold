# drupal_circleci_template
## Template CI configuration for contrib modules testing on CircleCI.

[![Circle CI](https://circleci.com/gh/alexdesignworks/drupal_circleci_template.svg?style=shield)](https://circleci.com/gh/alexdesignworks/drupal_circleci_template)

## Usage
1. Create your module's repository on GitHub (and add it as additional remote to your local git repo).
2. Copy `circle.yml` from this repo into your module's repository.
3. Adjust `MODULE_NAME` and `MODULE_TESTS` variables in `circle.yml'.
4. Commit and push to your new GitHub repo.
5. Login to CircleCI and add your new repository. Your project build will start momentarily.

You may put a build badge on your GitHub project page (like the one on this page). Unfortunately, it is not possible to put such bage on drupal.org project page (external sources are not supported), but you may put your GitHib project page as a homepage (resources tab in project settings).

----
Drupal 7 version is available on [`7.x` branch](https://github.com/alexdesignworks/drupal_circleci_template/tree/7.x)
