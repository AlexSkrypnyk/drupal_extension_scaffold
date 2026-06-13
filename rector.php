<?php

/**
 * @file
 * Rector configuration.
 *
 * Rector automatically refactors PHP code to:
 * - Upgrade deprecated Drupal APIs.
 * - Modernize PHP syntax to leverage new language features.
 * - Improve code quality and maintainability.
 *
 * @see https://github.com/palantirnet/drupal-rector
 * @see https://getrector.com/documentation
 * @see https://getrector.com/documentation/set-lists
 */

declare(strict_types=1);

use DrupalFinder\DrupalFinderComposerRuntime;
use DrupalRector\Set\Drupal10SetList;
use DrupalRector\Set\Drupal11SetList;
use DrupalRector\Set\Drupal9SetList;
use Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector;
use Rector\CodeQuality\Rector\ClassMethod\InlineArrayReturnAssignRector;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\CodingStyle\Rector\FuncCall\CountArrayToEmptyArrayComparisonRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameVariableToMatchNewTypeRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchMethodCallReturnTypeRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
use Rector\Privatization\Rector\MethodCall\PrivatizeLocalGetterToPropertyRector;
use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
  ->withSkip([
    // Specific rules to skip based on project coding standards.
    CatchExceptionNameMatchingTypeRector::class,
    ChangeSwitchToMatchRector::class,
    CompleteDynamicPropertiesRector::class,
    CountArrayToEmptyArrayComparisonRector::class,
    DisallowedEmptyRuleFixerRector::class,
    InlineArrayReturnAssignRector::class,
    NewlineAfterStatementRector::class,
    NewlineBeforeNewAssignSetRector::class,
    PrivatizeFinalClassMethodRector::class,
    PrivatizeFinalClassPropertyRector::class,
    PrivatizeLocalGetterToPropertyRector::class,
    RemoveAlwaysTrueIfConditionRector::class,
    RenameForeachValueVariableToMatchExprVariableRector::class,
    RenameForeachValueVariableToMatchMethodCallReturnTypeRector::class,
    RenameParamToMatchTypeRector::class,
    RenameVariableToMatchMethodCallReturnTypeRector::class,
    RenameVariableToMatchNewTypeRector::class,
    SimplifyEmptyCheckOnEmptyArrayRector::class,
    StringClassNameToClassConstantRector::class,
    // Directories to skip.
    '*/node_modules/*',
  ])
  // PHP version upgrade sets - modernizes syntax to PHP 8.2.
  // Includes all rules from PHP 5.3 through 8.2.
  ->withPhpSets(php82: TRUE)
  // Code quality improvement sets.
  ->withPreparedSets(
    deadCode: TRUE,
    codeQuality: TRUE,
    codingStyle: TRUE,
    typeDeclarations: TRUE,
    privatization: TRUE,
    naming: TRUE,
  )
  // Drupal-specific deprecation fixes.
  ->withSets([
    Drupal9SetList::DRUPAL_9,
    Drupal10SetList::DRUPAL_10,
    Drupal11SetList::DRUPAL_11,
  ])
  // Additional rules.
  ->withRules([
    DeclareStrictTypesRector::class,
  ])
  // Paths to the extension's source. Each top-level item of the extension is
  // symlinked individually into the build and Rector's finder does not follow
  // symlinked directories, so the module children are matched directly (each is
  // passed to the finder as its own root, which a symlinked root resolves).
  ->withPaths([
    __DIR__ . '/web/modules/custom/*/*',
    __DIR__ . '/web/themes/custom/*/*',
  ])
  // Configure Drupal autoloading.
  ->withAutoloadPaths((function (): array {
    $drupal_finder = new DrupalFinderComposerRuntime();
    $drupal_root = $drupal_finder->getDrupalRoot();

    return [
      $drupal_root . '/core',
      $drupal_root . '/modules',
      $drupal_root . '/themes',
      $drupal_root . '/profiles',
    ];
  })())
  // Drupal file extensions.
  ->withFileExtensions([
    'php',
    'module',
    'install',
    'profile',
    'theme',
    'inc',
    'engine',
  ])
  // Import configuration.
  ->withImportNames(importNames: TRUE, importDocBlockNames: FALSE, importShortClasses: FALSE);
