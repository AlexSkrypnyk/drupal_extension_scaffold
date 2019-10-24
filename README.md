# Drupal CircleCI
Template CI configuration for Drupal contrib modules testing on CircleCI
with mirroring to Drupal.org. 

[![CircleCI](https://circleci.com/gh/integratedexperts/drupal_circleci.svg?style=shield)](https://circleci.com/gh/integratedexperts/drupal_circleci)

## Use case
Perform module development in GitHub with testing in CircleCI, and push code 
committed only to main branches (`8.x-1.x` etc.) to drupal.org.

## Features
- Turnkey CI configuration with artifacts and test results support.
- PHP code standards checking against `Drupal` and `DrupalPractice` standards.
- Drupal's Simpletest testing support - runs tests in the same way as 
  drupal.org's Drupal CI bot (`core/scripts/run-tests.sh`).
- Uses [drupal-composer/drupal-project](https://github.com/drupal-composer/drupal-project) 
  to provision Drupal site.
- Mirroring of the repo to drupal.org (or any other git repo) on release.  
- Builder container is based on official PHP docker image.
- This template is tested in the same way as a project using it.

## Usage
1. Create your module's repository on GitHub.
2. Copy `.circle` from this repo into your module's repository.
3. Copy `.gitattributes` from this repo into your module's repository and 
   make sure to uncomment 2 lines to exclude `.circleci` and `.gitattributes`
   from exports.
4. Commit and push to your new GitHub repo.
5. Login to CircleCI and add your new GitHub repository. Your project build will 
   start momentarily.
   
## Deployment
The CI supports mirroring of main branches (`8.x-1.x` etc.) to drupal.org mirror 
of the project (to keep 2 repos in sync). 

The deployment job fires when commits are pushed to main branches 
(`8.x-1.x` etc.) or when release tags created. 

Example of deployment repo: https://github.com/integratedexperts/drupal_circleci_destination

Configure deployment:
1. In CircleCI UI, go to your project -> Settings -> SSH Permissions
2. Put your private SSH key into the box (this key must be added to your 
   drupal.org account so that CI would push as you).  
3. Copy fingerprint string and replace `deploy_ssh_fingerprint` value in 
   `.circleci/config.yml`.
4. In CircleCI UI go to your project -> Settings -> Environment Variables and 
   add the following variables through CircleCI UI:
   - `DEPLOY_USER_NAME` - the name of the user who will be committing to a 
     remote repository (your name on drupal.org).  
   - `DEPLOY_USER_EMAIL` - the email address of the user who will be committing 
     to a remote repository (your email on drupal.org).
   - `DEPLOY_REMOTE` - remote drupal.org repository.
   - `DEPLOY_PROCEED` - set to `1` once CI is working and you are ready to 
     deploy.
        
----
Drupal 7 version is available on [`7.x` branch](https://github.com/integratedexperts/drupal_circleci/tree/7.x)
