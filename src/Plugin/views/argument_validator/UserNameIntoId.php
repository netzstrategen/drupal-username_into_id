<?php

namespace Drupal\username_into_id\Plugin\views\argument_validator;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\user\Plugin\views\argument_validator\UserName;

/**
 * Validates whether an argument is a username, and if so, converts into the corresponding user ID.
 *
 * @ViewsArgumentValidator(
 *   id = "username_into_id",
 *   title = @Translation("Username into ID"),
 *   entity_type = "user"
 * )
 */
class UserNameIntoId extends UserName {

  /**
   * {@inheritdoc}
   */
  public function validateArgument($argument) {
    if ($this->multipleCapable && $this->options['multiple']) {
      $delimiters = [];
      $names = [];
      // To recreate the argument string, keep the delimiters.
      $args = preg_split('/([,+ ])/', $argument, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
      foreach ($args as $arg) {
        if (\in_array($arg, [',', '+'])) {
          $delimiters[] = $arg;
        }
        else {
          $names[] = $arg;
        }
      }
    }
    elseif ($argument) {
      $names = [$argument];
    }
    // No specified argument should be invalid.
    else {
      return FALSE;
    }
    $accounts = $this->userStorage->loadByProperties(['name' => $names]);
    // If there are no accounts, return FALSE now. As we will not enter the
    // loop below otherwise.
    if (empty($accounts)) {
      return FALSE;
    }
    $args = [];
    foreach ($accounts as $account) {
      if (!\in_array($account->getUserName(), $names) || !$this->validateEntity($account)) {
        continue;
      }
      // Do not prepend a delimiter on the first iteration.
      if (!empty($args)) {
        $args[] = array_shift($delimiters);
      }
      $args[] = (int) $account->id();
    }
    if (empty($args)) {
      return FALSE;
    }
    $this->argument->argument = implode('', $args);
    return TRUE;
  }

}
