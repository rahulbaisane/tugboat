<?php
namespace Drupal\tugboat;

use Drupal\Core\Site\Settings;

/**
 * Class TugboatService.
 */

class TugboatService {
  /**
 * Execute a Tugboat CLI command.
 *
 * @param $command_string
 *   The tugboat command string with any options. The --api-token and --json
 *   options are passed automatically. Tugboat command portion should be
 *   specified in this string, such as "find <id>" or "stop <id>".
 * @param array $return_data
 *   Returned data as an array if the command was successful.
 * @param $return_error_string
 *   A single error string if tugboat returned an error or if it is not possible
 *   to reach tugboat.
 * @param string $executable_path
 *   Optional. The path to the executable on the server. If not provided, the
 *   executable path provided in the config file will be used.
 *
 * @return bool
 */

  /**
   * Implements _tugboat_execute().
   */
  public function _tugboat_execute($command_string, array &$return_data, &$return_error_string, $executable_path = NULL) {
    $config = \Drupal::config('tugboat.settings');
    $api_token = Settings::get('tugboat_token');

    if (empty($executable_path)) {
      $executable_path = $config->get('tugboat_executable_path');
    }

    // Ensure binary is executable.
    if (!is_file($executable_path)) {
      $return_error_string = t('No tugboat executable file found at the provided path.');
      return FALSE;
    }
    elseif (!is_executable($executable_path)) {
      $return_error_string = t('The Tugboat CLI binary was found, but it is not executable.');
      return FALSE;
    }

    // Ensure input string is safe from any dangerous characters.
    // Characters allowed: 0-9, a-z, ., =, +, -, ', and a blank space.
    if (!preg_match('/^[0-9a-z=+\-\' ]+$/', $command_string)) {
      $return_error_string = t('Invalid character for Tugboat command. String given: @string', array('@string' => $command_string));
      return FALSE;
    }

    // Fire off the command via the binary file.
    $pipe_spec = array(
      0 => array("pipe", "r"),  // stdin pipe to send input.
      1 => array("pipe", "w"),  // stdout pipe to receive output.
      2 => array("pipe", "w")   // errors pipe to receive output.
    );
    $pipes = array();

    $command = "$executable_path --api-token='$api_token' $command_string --json";
    $process = proc_open($command, $pipe_spec, $pipes);
    fclose($pipes[0]);
    $std_output = stream_get_contents($pipes[1]);
    $error_output = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $process_status = proc_get_status($process);
    $process_exit_code = $process_status['exitcode'];
    proc_close($process);

    if ($error_output) {
      $return_error_string = trim($error_output);
    }
    if ($std_output) {
      $decoded_json = json_decode($std_output, TRUE);
      if ($decoded_json === NULL) {
        // Work-around https://github.com/Lullabot/tugboat/issues/2999.
        // Use the last line of JSON output and ignore any progress information.
        if ($process_exit_code === 0) {
          $lines = explode("\n", $std_output);
          $last_line = end($lines);
          if ($decoded_json = json_decode($last_line, TRUE)) {
            $return_data = $decoded_json;
          }
        }

        $return_error_string = 'Unparseable JSON returned.';
      }
      else {
        $return_data = $decoded_json;
      }
    }
    return $process_exit_code === 0;
  }
}
