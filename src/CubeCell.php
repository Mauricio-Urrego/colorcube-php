<?php

namespace Mauriciourrego\ColorcubePhp;

require_once  __DIR__ . '/../vendor/autoload.php';

class CubeCell
{
  /**
   * The color cube is made out of these cells.
   */

  public $hit_count;
  public $r_acc;
  public $g_acc;
  public $b_acc;

  public function __construct()
  {
    // Count of hits (dividing the accumulators by this value gives the average color).
    $this->hit_count = 0;
    // Accumulators for color components.
    $this->r_acc = 0.0;
    $this->g_acc = 0.0;
    $this->b_acc = 0.0;
  }
}
