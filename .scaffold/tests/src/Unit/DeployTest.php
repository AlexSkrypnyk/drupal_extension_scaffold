<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Unit;

use AlexSkrypnyk\drupal_extension_scaffold\Tests\Exceptions\QuitErrorException;
use AlexSkrypnyk\drupal_extension_scaffold\Tests\Exceptions\QuitSuccessException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for the deploy devtools script.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
#[RunTestsInSeparateProcesses]
#[Group('p0')]
final class DeployTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    require_once dirname(__DIR__, 4) . '/.devtools/helpers.php';
  }

  public function testDeploySkipWhenProceedNotSet(): void {
    $this->envSet('DEPLOY_USER_NAME', 'Test User');
    $this->envSet('DEPLOY_USER_EMAIL', 'test@example.com');
    $this->envSet('DEPLOY_REMOTE', 'git@git.drupal.org:project/test.git');

    $this->mockQuit(0);

    ob_start();
    try {
      require dirname(__DIR__, 4) . '/.devtools/deploy';
      $this->fail('Expected QuitSuccessException to be thrown');
    }
    catch (QuitSuccessException $e) {
      $this->assertSame(0, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('DEPLOY', $output);
      $this->assertStringContainsString('Skip deployment because DEPLOY_PROCEED is not set to 1', $output);
    }
  }

  public function testDeploySkipWhenProceedExplicitlyZero(): void {
    $this->envSet('DEPLOY_USER_NAME', 'Test User');
    $this->envSet('DEPLOY_USER_EMAIL', 'test@example.com');
    $this->envSet('DEPLOY_REMOTE', 'git@git.drupal.org:project/test.git');
    $this->envSet('DEPLOY_PROCEED', '0');

    $this->mockQuit(0);

    ob_start();
    try {
      require dirname(__DIR__, 4) . '/.devtools/deploy';
      $this->fail('Expected QuitSuccessException to be thrown');
    }
    catch (QuitSuccessException $e) {
      $this->assertSame(0, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Skip deployment', $output);
    }
  }

  #[DataProvider('dataProviderDeployProceed')]
  public function testDeployProceed(string $deploy_branch, string $git_user_name, string $git_user_email): void {
    $deploy_remote = 'git@git.drupal.org:project/test.git';

    $this->envSet('DEPLOY_USER_NAME', 'Deploy Bot');
    $this->envSet('DEPLOY_USER_EMAIL', 'deploy@example.com');
    $this->envSet('DEPLOY_REMOTE', $deploy_remote);
    $this->envSet('DEPLOY_PROCEED', '1');

    if ($deploy_branch !== '') {
      $this->envSet('DEPLOY_BRANCH', $deploy_branch);
    }

    $shell_exec_calls = [];
    $this->registerMock('shell_exec', 'DrupalExtensionScaffold\\DevTools', function (string $cmd) use (&$shell_exec_calls, $git_user_name, $git_user_email): string {
      $shell_exec_calls[] = $cmd;
      if (str_contains($cmd, 'user.name')) {
        return $git_user_name;
      }
      if (str_contains($cmd, 'user.email')) {
        return $git_user_email;
      }
      if (str_contains($cmd, 'symbolic-ref')) {
        return 'main';
      }
      return '';
    });

    $passthru_responses = [];

    // Configure git user name if not set.
    if (trim($git_user_name) === '') {
      $passthru_responses[] = ['cmd' => sprintf('git config --global user.name %s', escapeshellarg('Deploy Bot'))];
    }

    // Configure git user email if not set.
    if (trim($git_user_email) === '') {
      $passthru_responses[] = ['cmd' => sprintf('git config --global user.email %s', escapeshellarg('deploy@example.com'))];
    }

    // Push default matching.
    $passthru_responses[] = ['cmd' => 'git config --global push.default matching'];

    // Add remote.
    $passthru_responses[] = ['cmd' => sprintf('git remote add deployremote %s', escapeshellarg($deploy_remote))];

    // Push code.
    $effective_branch = $deploy_branch !== '' ? $deploy_branch : 'main';
    $passthru_responses[] = ['cmd' => sprintf('git push --force deployremote HEAD:%s', escapeshellarg($effective_branch))];

    // Push tags.
    $passthru_responses[] = ['cmd' => 'git push --force --tags deployremote 2>/dev/null || true'];

    $this->mockPassthruMultiple($passthru_responses);

    ob_start();
    require dirname(__DIR__, 4) . '/.devtools/deploy';
    $output = ob_get_clean();

    $this->assertIsString($output);
    $this->assertStringContainsString('DEPLOY', $output);
    $this->assertStringContainsString('Pushing code to branch ' . $effective_branch, $output);
    $this->assertStringContainsString('Code pushed to ' . $deploy_remote . ':' . $effective_branch, $output);
    $this->assertStringContainsString('Tags pushed to ' . $deploy_remote, $output);
    $this->assertStringContainsString('DEPLOY COMPLETE', $output);
    $this->assertStringContainsString('Remote URL    : ' . $deploy_remote, $output);
    $this->assertStringContainsString('Remote branch : ' . $effective_branch, $output);
  }

  public static function dataProviderDeployProceed(): \Iterator {
    yield 'custom branch, no existing git config' => [
      'deploy_branch' => '1.x',
      'git_user_name' => '',
      'git_user_email' => '',
    ];
    yield 'auto-detect branch, existing git config' => [
      'deploy_branch' => '',
      'git_user_name' => 'Existing User',
      'git_user_email' => 'existing@example.com',
    ];
    yield 'custom branch, existing user name only' => [
      'deploy_branch' => '2.x',
      'git_user_name' => 'Existing User',
      'git_user_email' => '',
    ];
    yield 'auto-detect branch, existing email only' => [
      'deploy_branch' => '',
      'git_user_name' => '',
      'git_user_email' => 'existing@example.com',
    ];
  }

  public function testDeployMissingRequiredVars(): void {
    // DEPLOY_USER_NAME is not set - should fail.
    $this->mockQuit(1);

    ob_start();
    try {
      require dirname(__DIR__, 4) . '/.devtools/deploy';
      $this->fail('Expected QuitErrorException to be thrown');
    }
    catch (QuitErrorException $e) {
      $this->assertSame(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Missing required value for DEPLOY_USER_NAME', $output);
    }
  }

  public function testDeploySshKeyMd5Fingerprint(): void {
    $this->envSet('DEPLOY_USER_NAME', 'Test User');
    $this->envSet('DEPLOY_USER_EMAIL', 'test@example.com');
    $this->envSet('DEPLOY_REMOTE', 'git@git.drupal.org:project/test.git');
    $this->envSet('DEPLOY_SSH_KEY_FINGERPRINT', 'aa:bb:cc:dd:ee:ff');
    $this->envSet('HOME', '/home/testuser');

    // Mock is_dir for SSH directory.
    $this->registerMock('is_dir', 'DrupalExtensionScaffold\\DevTools', fn(string $path): bool => str_contains($path, '.ssh'));

    // Mock file_put_contents for SSH config.
    $this->registerMock('file_put_contents', 'DrupalExtensionScaffold\\DevTools', fn(): int => 100);

    // After cleaning fingerprint: aabbccddeeff.
    $key_file = '/home/testuser/.ssh/id_rsa_aabbccddeeff';

    // Mock file_exists for key file.
    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', fn(string $file): bool => $file === $key_file);

    // SSH_AGENT_PID is set.
    $this->envSet('SSH_AGENT_PID', '12345');

    $this->mockPassthruMultiple([
      ['cmd' => 'ssh-add -D'],
      ['cmd' => sprintf('ssh-add %s', escapeshellarg($key_file))],
      ['cmd' => 'ssh-add -l'],
    ]);

    // DEPLOY_PROCEED is not 1, so it skips.
    $this->mockQuit(0);

    ob_start();
    try {
      require dirname(__DIR__, 4) . '/.devtools/deploy';
      $this->fail('Expected QuitSuccessException to be thrown');
    }
    catch (QuitSuccessException) {
      // Expected.
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Setup SSH', $output);
      $this->assertStringContainsString('Skip deployment', $output);
    }
  }

  public function testDeploySshKeySha256Fingerprint(): void {
    $this->envSet('DEPLOY_USER_NAME', 'Test User');
    $this->envSet('DEPLOY_USER_EMAIL', 'test@example.com');
    $this->envSet('DEPLOY_REMOTE', 'git@git.drupal.org:project/test.git');
    $this->envSet('DEPLOY_SSH_KEY_FINGERPRINT', 'SHA256:abcdef123456');
    $this->envSet('HOME', '/home/testuser');

    // Mock is_dir - SSH dir exists.
    $this->registerMock('is_dir', 'DrupalExtensionScaffold\\DevTools', fn(string $path): bool => str_contains($path, '.ssh'));

    // Mock file_put_contents.
    $this->registerMock('file_put_contents', 'DrupalExtensionScaffold\\DevTools', fn(): int => 100);

    // Mock glob for SSH key discovery.
    $this->registerMock('glob', 'DrupalExtensionScaffold\\DevTools', fn(): array => ['/home/testuser/.ssh/id_rsa_test']);

    // Mock exec for ssh-keygen calls.
    $exec_calls = 0;
    $this->registerMock('exec', 'DrupalExtensionScaffold\\DevTools', function (string $cmd, ?array &$output = NULL) use (&$exec_calls): int {
      $exec_calls++;
      $output ??= [];
      if (str_contains($cmd, '-E sha256')) {
        $output[] = '2048 SHA256:abcdef123456 testuser@host (RSA)';
      }
      elseif (str_contains($cmd, '-E md5')) {
        $output[] = '2048 MD5:aa:bb:cc:dd testuser@host (RSA)';
      }
      return 0;
    });

    // After MD5 extraction and cleaning: aabbccdd.
    $key_file = '/home/testuser/.ssh/id_rsa_aabbccdd';

    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', fn(string $file): bool => $file === $key_file);

    $this->envSet('SSH_AGENT_PID', '12345');

    $this->mockPassthruMultiple([
      ['cmd' => 'ssh-add -D'],
      ['cmd' => sprintf('ssh-add %s', escapeshellarg($key_file))],
      ['cmd' => 'ssh-add -l'],
    ]);

    $this->mockQuit(0);

    ob_start();
    try {
      require dirname(__DIR__, 4) . '/.devtools/deploy';
      $this->fail('Expected QuitSuccessException to be thrown');
    }
    catch (QuitSuccessException) {
      // Expected.
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Setup SSH', $output);
    }
  }

  public function testDeploySshKeyNotFound(): void {
    $this->envSet('DEPLOY_USER_NAME', 'Test User');
    $this->envSet('DEPLOY_USER_EMAIL', 'test@example.com');
    $this->envSet('DEPLOY_REMOTE', 'git@git.drupal.org:project/test.git');
    $this->envSet('DEPLOY_SSH_KEY_FINGERPRINT', 'aa:bb:cc:dd');
    $this->envSet('HOME', '/home/testuser');

    $this->registerMock('is_dir', 'DrupalExtensionScaffold\\DevTools', fn(): true => TRUE);
    $this->registerMock('file_put_contents', 'DrupalExtensionScaffold\\DevTools', fn(): int => 100);

    // Key file does not exist.
    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', fn(): false => FALSE);

    $this->mockQuit(1);

    ob_start();
    try {
      require dirname(__DIR__, 4) . '/.devtools/deploy';
      $this->fail('Expected QuitErrorException to be thrown');
    }
    catch (QuitErrorException $e) {
      $this->assertSame(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Unable to find SSH key file', $output);
    }
  }

  public function testDeploySshCreatesSshDir(): void {
    $this->envSet('DEPLOY_USER_NAME', 'Test User');
    $this->envSet('DEPLOY_USER_EMAIL', 'test@example.com');
    $this->envSet('DEPLOY_REMOTE', 'git@git.drupal.org:project/test.git');
    $this->envSet('DEPLOY_SSH_KEY_FINGERPRINT', 'aa:bb:cc:dd');
    $this->envSet('HOME', '/home/testuser');

    // SSH dir does not exist.
    $this->registerMock('is_dir', 'DrupalExtensionScaffold\\DevTools', fn(): false => FALSE);

    $mkdir_calls = [];
    $this->registerMock('mkdir', 'DrupalExtensionScaffold\\DevTools', function (string $dir) use (&$mkdir_calls): true {
      $mkdir_calls[] = $dir;
      return TRUE;
    });

    $this->registerMock('file_put_contents', 'DrupalExtensionScaffold\\DevTools', fn(): int => 100);

    $key_file = '/home/testuser/.ssh/id_rsa_aabbccdd';
    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', fn(string $file): bool => $file === $key_file);

    $this->envSet('SSH_AGENT_PID', '12345');

    $this->mockPassthruMultiple([
      ['cmd' => 'ssh-add -D'],
      ['cmd' => sprintf('ssh-add %s', escapeshellarg($key_file))],
      ['cmd' => 'ssh-add -l'],
    ]);

    $this->mockQuit(0);

    ob_start();
    try {
      require dirname(__DIR__, 4) . '/.devtools/deploy';
      $this->fail('Expected QuitSuccessException to be thrown');
    }
    catch (QuitSuccessException) {
      // Expected.
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
    }

    $this->assertContains('/home/testuser/.ssh', $mkdir_calls);
  }

  public function testDeploySshAgentNotRunning(): void {
    $this->envSet('DEPLOY_USER_NAME', 'Test User');
    $this->envSet('DEPLOY_USER_EMAIL', 'test@example.com');
    $this->envSet('DEPLOY_REMOTE', 'git@git.drupal.org:project/test.git');
    $this->envSet('DEPLOY_SSH_KEY_FINGERPRINT', 'aa:bb:cc:dd');
    $this->envSet('HOME', '/home/testuser');
    // SSH_AGENT_PID is not set.

    $this->registerMock('is_dir', 'DrupalExtensionScaffold\\DevTools', fn(): true => TRUE);
    $this->registerMock('file_put_contents', 'DrupalExtensionScaffold\\DevTools', fn(): int => 100);

    $key_file = '/home/testuser/.ssh/id_rsa_aabbccdd';
    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', fn(string $file): bool => $file === $key_file);

    // SSH_AGENT_PID not set → starts ssh-agent first.
    $this->mockPassthruMultiple([
      ['cmd' => 'eval "$(ssh-agent)"'],
      ['cmd' => 'ssh-add -D'],
      ['cmd' => sprintf('ssh-add %s', escapeshellarg($key_file))],
      ['cmd' => 'ssh-add -l'],
    ]);

    $this->mockQuit(0);

    ob_start();
    try {
      require dirname(__DIR__, 4) . '/.devtools/deploy';
      $this->fail('Expected QuitSuccessException to be thrown');
    }
    catch (QuitSuccessException) {
      // Expected.
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Setup SSH', $output);
    }
  }

  public function testDeploySshSha256NoMatch(): void {
    $this->envSet('DEPLOY_USER_NAME', 'Test User');
    $this->envSet('DEPLOY_USER_EMAIL', 'test@example.com');
    $this->envSet('DEPLOY_REMOTE', 'git@git.drupal.org:project/test.git');
    $this->envSet('DEPLOY_SSH_KEY_FINGERPRINT', 'SHA256:nomatch');
    $this->envSet('HOME', '/home/testuser');

    $this->registerMock('is_dir', 'DrupalExtensionScaffold\\DevTools', fn(): true => TRUE);
    $this->registerMock('file_put_contents', 'DrupalExtensionScaffold\\DevTools', fn(): int => 100);

    // Keys exist but none match SHA256.
    $this->registerMock('glob', 'DrupalExtensionScaffold\\DevTools', fn(): array => ['/home/testuser/.ssh/id_rsa_test']);

    $this->registerMock('exec', 'DrupalExtensionScaffold\\DevTools', function (string $cmd, ?array &$output = NULL): int {
      $output ??= [];
      if (str_contains($cmd, '-E sha256')) {
        $output[] = '2048 SHA256:differenthash testuser@host (RSA)';
      }
      return 0;
    });

    // SHA256 fingerprint didn't get converted. Cleaned: SHA256nomatch.
    // Key file not found.
    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', fn(): false => FALSE);

    $this->mockQuit(1);

    ob_start();
    try {
      require dirname(__DIR__, 4) . '/.devtools/deploy';
      $this->fail('Expected QuitErrorException to be thrown');
    }
    catch (QuitErrorException $e) {
      $this->assertSame(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Unable to find SSH key file', $output);
    }
  }

  public function testDeploySshSha256EmptyGlob(): void {
    $this->envSet('DEPLOY_USER_NAME', 'Test User');
    $this->envSet('DEPLOY_USER_EMAIL', 'test@example.com');
    $this->envSet('DEPLOY_REMOTE', 'git@git.drupal.org:project/test.git');
    $this->envSet('DEPLOY_SSH_KEY_FINGERPRINT', 'SHA256:abcdef');
    $this->envSet('HOME', '/home/testuser');

    $this->registerMock('is_dir', 'DrupalExtensionScaffold\\DevTools', fn(): true => TRUE);
    $this->registerMock('file_put_contents', 'DrupalExtensionScaffold\\DevTools', fn(): int => 100);

    // No key files found.
    $this->registerMock('glob', 'DrupalExtensionScaffold\\DevTools', fn(): false => FALSE);

    // Key file not found (SHA256 was not converted).
    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', fn(): false => FALSE);

    $this->mockQuit(1);

    ob_start();
    try {
      require dirname(__DIR__, 4) . '/.devtools/deploy';
      $this->fail('Expected QuitErrorException to be thrown');
    }
    catch (QuitErrorException $e) {
      $this->assertSame(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Unable to find SSH key file', $output);
    }
  }

  public function testDeploySshSha256EmptyExecOutput(): void {
    $this->envSet('DEPLOY_USER_NAME', 'Test User');
    $this->envSet('DEPLOY_USER_EMAIL', 'test@example.com');
    $this->envSet('DEPLOY_REMOTE', 'git@git.drupal.org:project/test.git');
    $this->envSet('DEPLOY_SSH_KEY_FINGERPRINT', 'SHA256:abcdef');
    $this->envSet('HOME', '/home/testuser');

    $this->registerMock('is_dir', 'DrupalExtensionScaffold\\DevTools', fn(): true => TRUE);
    $this->registerMock('file_put_contents', 'DrupalExtensionScaffold\\DevTools', fn(): int => 100);

    $this->registerMock('glob', 'DrupalExtensionScaffold\\DevTools', fn(): array => ['/home/testuser/.ssh/id_rsa_test']);

    // exec returns empty output.
    $this->registerMock('exec', 'DrupalExtensionScaffold\\DevTools', function (string $cmd, ?array &$output = NULL): int {
      $output ??= [];
      // Empty output - no fingerprint information.
      return 0;
    });

    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', fn(): false => FALSE);

    $this->mockQuit(1);

    ob_start();
    try {
      require dirname(__DIR__, 4) . '/.devtools/deploy';
      $this->fail('Expected QuitErrorException to be thrown');
    }
    catch (QuitErrorException $e) {
      $this->assertSame(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Unable to find SSH key file', $output);
    }
  }

  public function testDeploySshAgentPidEmpty(): void {
    $this->envSet('DEPLOY_USER_NAME', 'Test User');
    $this->envSet('DEPLOY_USER_EMAIL', 'test@example.com');
    $this->envSet('DEPLOY_REMOTE', 'git@git.drupal.org:project/test.git');
    $this->envSet('DEPLOY_SSH_KEY_FINGERPRINT', 'aa:bb:cc:dd');
    $this->envSet('HOME', '/home/testuser');
    // Set SSH_AGENT_PID to empty string - should still start agent.
    $this->envSet('SSH_AGENT_PID', '');

    $this->registerMock('is_dir', 'DrupalExtensionScaffold\\DevTools', fn(): true => TRUE);
    $this->registerMock('file_put_contents', 'DrupalExtensionScaffold\\DevTools', fn(): int => 100);

    $key_file = '/home/testuser/.ssh/id_rsa_aabbccdd';
    $this->registerMock('file_exists', 'DrupalExtensionScaffold\\DevTools', fn(string $file): bool => $file === $key_file);

    // Empty SSH_AGENT_PID → starts ssh-agent.
    $this->mockPassthruMultiple([
      ['cmd' => 'eval "$(ssh-agent)"'],
      ['cmd' => 'ssh-add -D'],
      ['cmd' => sprintf('ssh-add %s', escapeshellarg($key_file))],
      ['cmd' => 'ssh-add -l'],
    ]);

    $this->mockQuit(0);

    ob_start();
    try {
      require dirname(__DIR__, 4) . '/.devtools/deploy';
      $this->fail('Expected QuitSuccessException to be thrown');
    }
    catch (QuitSuccessException) {
      // Expected.
    }
    finally {
      ob_get_clean();
    }
  }

}
