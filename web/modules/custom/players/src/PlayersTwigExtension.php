<?php

namespace Drupal\players;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class PlayersTwigExtension extends AbstractExtension
{
  /**
   * Generates a list of all Twig functions that this extension defines.
   */
  public function getFunctions(): array
  {
    return [
      new TwigFunction('upper_case', [$this, 'toUpperCase']),
    ];
  }

  public function getFilters(): array
  {
    return [
      new TwigFilter('upper_case', [$this, 'toUpperCase']),
    ];
  }

  /**
   * Converts a string to upper case.
   */
  public function toUpperCase($string): string
  {
    return strtoupper($string);
  }
}
