<?php

namespace Mauriciourrego\ColorcubePhp;

require_once  __DIR__ . '/../vendor/autoload.php';

class CubeCell {
  /**
   * The color cube is made out of these cells.
   *
   * @var $hit_count int
   * @var $r_acc float
   * @var $g_acc float
   * @var $b_acc float
   */
  private $hit_count;
  private $r_acc;
  private $g_acc;
  private $b_acc;

  public function __construct() {
    // Count of hits (dividing the accumulators by this value gives the average color).
    $this->hit_count = 0;
    // Accumulators for color components.
    $this->r_acc = 0.0;
    $this->g_acc = 0.0;
    $this->b_acc = 0.0;
  }
}