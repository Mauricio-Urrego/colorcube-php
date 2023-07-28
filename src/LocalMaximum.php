<?php

namespace Mauriciourrego\ColorcubePhp;

require_once  __DIR__ . '/../vendor/autoload.php';

class LocalMaximum
{
  /**
   * Local maxima as found during the image analysis->
   * We need this class for ordering by cell hit count->
   */

  public $hit_count;
  public $cell_index;
  public $r;
  public $g;
  public $b;

  public function __construct($hit_count, $cell_index, $r, $g, $b)
  {
    // Hit count of the cell.
    $this->hit_count = $hit_count;
    // Linear index of the cell.
    $this->cell_index = $cell_index;
    // Average color of the cell.
    $this->r = $r;
    $this->g = $g;
    $this->b = $b;
  }
}
