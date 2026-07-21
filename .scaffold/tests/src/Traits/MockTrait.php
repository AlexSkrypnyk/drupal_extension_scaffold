<?php

declare(strict_types=1);

namespace AlexSkrypnyk\drupal_extension_scaffold\Tests\Traits;

use AlexSkrypnyk\drupal_extension_scaffold\Tests\Exceptions\QuitErrorException;
use AlexSkrypnyk\drupal_extension_scaffold\Tests\Exceptions\QuitSuccessException;
use phpmock\phpunit\PHPMock;

trait MockTrait {

  use PHPMock;

  /**
   * Unified registry of all mock objects indexed by function name.
   *
   * @var array<string, \PHPUnit\Framework\MockObject\MockObject|array<string, \PHPUnit\Framework\MockObject\MockObject>>
   */
  protected array $mocks = [];

  /**
   * Unified registry of all mock responses indexed by function name.
   *
   * @var array<string, array<int, array<string, mixed>>>
   */
  protected array $mockResponses = [];

  /**
   * Unified registry of response indices indexed by function name.
   *
   * @var array<string, int>
   */
  protected array $mockIndices = [];

  /**
   * Unified registry of checked flags indexed by function name.
   *
   * @var array<string, bool>
   */
  protected array $mockChecked = [];

  protected function mockTearDown(): void {
    // Assert all mocks consumed using unified infrastructure.
    foreach (array_keys($this->mocks) as $function_name) {
      $this->assertMockConsumed($function_name);
    }

    // Reset all mocks using unified infrastructure.
    foreach (array_keys($this->mocks) as $function_name) {
      $this->resetMock($function_name);
    }

    // Clear unified registries.
    $this->mocks = [];
    $this->mockResponses = [];
    $this->mockIndices = [];
    $this->mockChecked = [];
  }

  /**
   * Register a new mock function in the unified registry.
   *
   * @param string $function_name
   *   The function name to mock (e.g., 'passthru', 'mail').
   * @param string $namespace
   *   The namespace where the function should be mocked.
   * @param callable $callback
   *   The callback to execute when the mocked function is called.
   */
  protected function registerMock(string $function_name, string $namespace, callable $callback): void {
    // Create the mock object.
    $mock = $this->getFunctionMock($namespace, $function_name);
    $mock->expects($this->any())->willReturnCallback($callback);

    // Store in registry.
    $this->mocks[$function_name] = $mock;
  }

  /**
   * Add responses for a mock function.
   *
   * @param string $function_name
   *   The function name (e.g., 'passthru', 'mail').
   * @param array<int, array<string, mixed>> $responses
   *   Array of response configurations.
   */
  protected function addMockResponses(string $function_name, array $responses): void {
    // Initialize if not exists.
    if (!isset($this->mockResponses[$function_name])) {
      $this->mockResponses[$function_name] = [];
      $this->mockIndices[$function_name] = 0;
    }

    // Add responses to the queue.
    $this->mockResponses[$function_name] = array_merge(
      $this->mockResponses[$function_name],
      $responses
    );

    // Reset checked flag so teardown re-validates consumption.
    $this->mockChecked[$function_name] = FALSE;
  }

  /**
   * Get the next response for a mock function.
   *
   * @param string $function_name
   *   The function name.
   *
   * @return array<string, mixed>
   *   The next response configuration.
   *
   * @throws \RuntimeException
   *   When no more responses are available.
   */
  protected function getNextMockResponse(string $function_name): array {
    $total_responses = count($this->mockResponses[$function_name] ?? []);
    $current_index = $this->mockIndices[$function_name] ?? 0;

    if ($current_index >= $total_responses) {
      throw new \RuntimeException(sprintf(
        '%s() called more times than mocked responses. Expected %d call(s), but attempting call #%d.',
        $function_name,
        $total_responses,
        $current_index + 1
      ));
    }

    $response = $this->mockResponses[$function_name][$current_index];
    $this->mockIndices[$function_name]++;

    return $response;
  }

  /**
   * Assert all responses were consumed for a mock function.
   *
   * @param string $function_name
   *   The function name.
   *
   * @throws \PHPUnit\Framework\AssertionFailedError
   *   When not all responses were consumed.
   */
  protected function assertMockConsumed(string $function_name): void {
    // Skip if no mock exists for this function.
    if (!isset($this->mocks[$function_name])) {
      return;
    }

    // Skip if already checked.
    if (isset($this->mockChecked[$function_name]) && $this->mockChecked[$function_name]) {
      return;
    }

    $this->mockChecked[$function_name] = TRUE;

    $total_responses = count($this->mockResponses[$function_name] ?? []);
    $consumed_responses = $this->mockIndices[$function_name] ?? 0;

    if ($consumed_responses < $total_responses) {
      $this->fail(sprintf(
        'Not all mocked %s responses were consumed. Expected %d call(s), but only %d call(s) were made.',
        $function_name,
        $total_responses,
        $consumed_responses
      ));
    }
  }

  /**
   * Reset a mock function's state.
   *
   * @param string $function_name
   *   The function name.
   */
  protected function resetMock(string $function_name): void {
    unset($this->mocks[$function_name]);
    unset($this->mockResponses[$function_name]);
    unset($this->mockIndices[$function_name]);
    unset($this->mockChecked[$function_name]);
  }

  /**
   * Mock passthru function to return predefined output and exit codes.
   *
   * @param array<int, array{cmd:string, output?: string, result_code?: int, return?: NULL|FALSE}> $responses
   *   Array of responses to return for each passthru call.
   * @param string $namespace
   *   Namespace to mock the functions in.
   *
   * @throws \RuntimeException
   *   When more passthru calls are made than mocked responses available.
   */
  protected function mockPassthruMultiple(array $responses, string $namespace = 'DrupalExtensionScaffold\\DevTools'): void {
    // Add responses to unified registry.
    $this->addMockResponses('passthru', $responses);

    // If mock already exists, just add to responses and return.
    if (isset($this->mocks['passthru'])) {
      return;
    }

    // Register the mock using unified infrastructure.
    $this->registerMock('passthru', $namespace, function ($command, &...$args): null|false {
      $response = $this->getNextMockResponse('passthru');

      $response += [
        'output' => '',
        'result_code' => 0,
        'return' => NULL,
      ];

      // Validate response structure.
      // @phpstan-ignore-next-line isset.offset
      if (!isset($response['cmd'])) {
        throw new \InvalidArgumentException('Mocked passthru response must include "cmd" key to specify expected command.');
      }

      // @phpstan-ignore-next-line booleanAnd.alwaysFalse
      if ($response['return'] !== FALSE && $response['return'] !== NULL) {
        throw new \InvalidArgumentException(sprintf('Mocked passthru response "return" key must be either NULL or FALSE, but got %s.', gettype($response['return'])));
      }

      /** @var array{cmd: string, output: string, result_code: int, return: NULL|FALSE} $response */

      // The quiet-mode command runners - passthru_or_fail() and drush() without
      // DEBUG - wrap the command in a brace group ("{ <cmd>; } 2>&1") so a
      // normal run captures stdout and stderr together. Strip that wrapper
      // before matching so expectations assert the logical command regardless
      // of whether the runner is capturing or streaming (DEBUG=1) it. The
      // wrapper format itself is asserted in HelpersPassthruCaptureTest.
      $matched = $command;
      if (str_starts_with($matched, '{ ') && str_ends_with($matched, '; } 2>&1')) {
        $matched = substr($matched, 2, -8);
      }

      // Expectation error.
      if ($response['cmd'] !== $matched) {
        throw new \RuntimeException(sprintf('passthru() called with unexpected command. Expected "%s", got "%s".', $response['cmd'], $command));
      }

      echo $response['output'];

      // Set exit code only if it was passed by reference.
      // Using spread operator to distinguish between no argument and NULL.
      if (count($args) > 0) {
        $args[0] = $response['result_code'];
      }

      return $response['return'];
    });
  }

  /**
   * Mock single passthru call.
   *
   * @param array{cmd:string, output?: string, result_code?: int, return?: NULL|FALSE} $response
   *   Response with output and exit_code.
   * @param string $namespace
   *   Namespace to mock the functions in.
   */
  protected function mockPassthru(array $response, string $namespace = 'DrupalExtensionScaffold\\DevTools'): void {
    $this->mockPassthruMultiple([$response], $namespace);
  }

  /**
   * Verify all mocked passthru responses were consumed.
   *
   * @throws \PHPUnit\Framework\AssertionFailedError
   *   When not all mocked responses were consumed.
   */
  protected function mockPassthruAssertAllMocksConsumed(): void {
    $this->assertMockConsumed('passthru');
  }

  /**
   * Mock quit() function to throw QuitErrorException instead of terminating.
   *
   * @param int $code
   *   Exit code to expect (0 for success, non-zero for error).
   * @param string $namespace
   *   Namespace to mock the function in.
   */
  protected function mockQuit(int $code = 0, string $namespace = 'DrupalExtensionScaffold\\DevTools'): void {
    $quit = $this->getFunctionMock($namespace, 'quit');
    $quit
      ->expects($this->any())
      ->willReturnCallback(function (int $exit_code = 0) use ($code): void {
        // Expectation error.
        if ($code !== $exit_code) {
          throw new \RuntimeException(sprintf('quit() called with unexpected exit code. Expected %d, got %d.', $code, $exit_code));
        }
        // Non-zero exit code throws QuitErrorException to simulate exit.
        if ($code !== 0) {
          throw new QuitErrorException($code);
        }

        throw new QuitSuccessException($code);
      });

    // Store in unified registry.
    $this->mocks['quit'] = $quit;
  }

  /**
   * Mock posix_isatty function to control terminal color detection.
   *
   * @param bool $return_value
   *   The value to return (TRUE for TTY/color support, FALSE for no TTY).
   * @param string $namespace
   *   Namespace to mock the function in.
   */
  protected function mockPosixIsatty(bool $return_value, string $namespace = 'DrupalExtensionScaffold\\DevTools'): void {
    // Add single response to unified registry.
    $this->addMockResponses('posix_isatty', [['value' => $return_value]]);

    // Register mock if not already registered.
    if (!isset($this->mocks['posix_isatty'])) {
      $this->registerMock('posix_isatty', $namespace, function () {
        $response = $this->getNextMockResponse('posix_isatty');
        return $response['value'];
      });
    }
  }

  /**
   * Verify all mocked posix_isatty responses were consumed.
   *
   * @throws \PHPUnit\Framework\AssertionFailedError
   *   When not all mocked responses were consumed.
   */
  protected function mockPosixIsattyAssertAllMocksConsumed(): void {
    $this->assertMockConsumed('posix_isatty');
  }

  /**
   * Mock sleep() function as a no-op.
   *
   * @param string $namespace
   *   Namespace to mock the function in.
   */
  protected function mockSleep(string $namespace = 'DrupalExtensionScaffold\\DevTools'): void {
    if (isset($this->mocks['sleep'])) {
      return;
    }
    $mock = $this->getFunctionMock($namespace, 'sleep');
    $mock->expects($this->any())->willReturn(0);
    $this->mocks['sleep'] = $mock;
  }

}
