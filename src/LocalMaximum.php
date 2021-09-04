<?php

namespace Mauriciourrego\ColorcubePhp;

require_once  __DIR__ . '/../vendor/autoload.php';

class LocalMaximum {
  // Local maxima as found during the image analysis->
  // We need this class for ordering by cell hit count->
  private $hit_count;
  private $cell_index;
  private $r;
  private $g;
  private $b;

  public function __construct($hit_count, $cell_index, $r, $g, $b) {
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