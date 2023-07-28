<?php

namespace Mauriciourrego\ColorcubePhp;

require_once  __DIR__ . '/../vendor/autoload.php';

// The MIT License (MIT)

// Copyright (c) 2015 Ole Krause-Sparmann, Mauricio Urrego

// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in all
// copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
// SOFTWARE.

/**
 * Uses a 3d RGB histogram to find local maxims in the density distribution
 * in order to retrieve dominant colors of pixel images.
 */
class ColorCube
{

  public $resolution;
  public $distinct_threshold;
  public $avoid_color;
  public $bright_threshold;
  public $cell_count;
  public $cells;
  public $neighbour_indices;

  public function __construct($resolution = 30, $avoid_color = [
    255,
    255,
    255
  ], $distinct_threshold = 0.2, $bright_threshold = 0.6)
  {
    // Keep resolution.
    $this->resolution = $resolution;
    // Threshold for distinct local maxima.
    $this->distinct_threshold = $distinct_threshold;
    // Color to avoid.
    $this->avoid_color = $avoid_color;
    // Colors that are darker than this go away.
    $this->bright_threshold = $bright_threshold;
    // Helper variable to have cell count handy.
    $this->cell_count = $resolution ** 3; // $resolution to the 3rd power.
    // Create cells
    foreach (range(0, $this->cell_count - 1) as $k) {
      $this->cells[$k] = new CubeCell();
    }

    // Indices for neighbour cells in three-dimensional grid.
    $this->neighbour_indices = [
      [0, 0, 0],
      [0, 0, 1],
      [0, 0, -1],
      [0, 1, 0],
      [0, 1, 1],
      [0, 1, -1],
      [0, -1, 0],
      [0, -1, 1],
      [0, -1, -1],
      [1, 0, 0],
      [1, 0, 1],
      [1, 0, -1],
      [1, 1, 0],
      [1, 1, 1],
      [1, 1, -1],
      [1, -1, 0],
      [1, -1, 1],
      [1, -1, -1],
      [-1, 0, 0],
      [-1, 0, 1],
      [-1, 0, -1],
      [-1, 1, 0],
      [-1, 1, 1],
      [-1, 1, -1],
      [-1, -1, 0],
      [-1, -1, 1],
      [-1, -1, -1]
    ];
  }

  public function cell_index($r, $g, $b)
  {
    // Returns linear index for cell with given 3d index.
    return $r + $g * $this->resolution + $b * $this->resolution * $this->resolution;
  }

  public function clear_cells()
  {
    foreach ($this->cells as $c) {
      $c->hit_count = 0;
      $c->r_acc = 0.0;
      $c->g_acc = 0.0;
      $c->b_acc = 0.0;
    }
  }

  public function get_colors($image): array
  {
    $m = $this->find_local_maxima($image);
    if ($this->avoid_color !== 'None') {
      $m = $this->filter_too_similar($m);
    }
    $m = $this->filter_distinct_maxima($m);

    $colors = [];
    foreach ($m as $n) {
      $r = intval($n->r * 255.0);
      $g = intval($n->g * 255.0);
      $b = intval($n->b * 255.0);
      array_push($colors, [$r, $g, $b]);
    }

    return $colors;
  }

  public function find_local_maxima($image): array
  {
    // Finds and returns local maxima in 3d histogram, sorted with respect to hit count.
    // Reset all cells.
    $this->clear_cells();
    // Find the size, width, and height of the image.
    $width = imagesx($image);
    $height = imagesy($image);
    // Iterate over all pixels of the image.
    for ($x = 0; $x < $width; $x++) {
      for ($y = 0; $y < $height; $y++) {
        $p = imagecolorat($image, $x, $y);
        $p = imagecolorsforindex($image, $p);

        // Get color components.
        $r = floatval($p['red']) / 255.0;
        $g = floatval($p['green']) / 255.0;
        $b = floatval($p['blue']) / 255.0;
        if ($r < $this->bright_threshold and $g < $this->bright_threshold and $b < $this->bright_threshold) {
          continue;
        }
        // If image has alpha channel, weight colors by it.
        if (count($p) === 4 && $p['alpha'] !== 0) {
          $a = floatval($p['alpha']) / 255.0;
          $r *= $a;
          $g *= $a;
          $b *= $a;
        }
        // Map color components to cell indices in each color dimension.
        $r_index = intval($r * (floatval($this->resolution) - 1.0));
        $g_index = intval($g * (floatval($this->resolution) - 1.0));
        $b_index = intval($b * (floatval($this->resolution) - 1.0));
        // Compute linear cell index.
        $index = $this->cell_index($r_index, $g_index, $b_index);
        // Increase hit count of cell.
        $this->cells[$index]->hit_count += 1;
        // Add pixel colors to cell color accumulators.
        $this->cells[$index]->r_acc += $r;
        $this->cells[$index]->g_acc += $g;
        $this->cells[$index]->b_acc += $b;
      }
    }
    // We collect local maxima in here.
    $local_maxima = [];
    // Find local maxima in the grid.
    foreach (range(0, $this->resolution - 1) as $r) {
      foreach (range(0, $this->resolution - 1) as $g) {
        foreach (range(0, $this->resolution - 1) as $b) {
          $local_index = $this->cell_index($r, $g, $b);
          // Get hit count of this cell.
          $local_hit_count = $this->cells[$local_index]->hit_count;
          // If this cell has no hits, ignore it (we are not interested in zero hit cells).
          if ($local_hit_count === 0) {
            continue;
          }
          // It is a local maximum until we find a neighbour with a higher hit count.
          $is_local_maximum = TRUE;
          // Check if any neighbour has a higher hit count, if so, no local maxima.
          foreach (range(0, 26) as $n) {
            $r_index = $r + $this->neighbour_indices[$n][0];
            $g_index = $g + $this->neighbour_indices[$n][1];
            $b_index = $b + $this->neighbour_indices[$n][2];
            // Only check valid cell indices (skip out of bounds indices).
            if ($r_index >= 0 and $g_index >= 0 and $b_index >= 0) {
              if ($r_index < $this->resolution and $g_index < $this->resolution and $b_index < $this->resolution) {
                if ($this->cells[$this->cell_index($r_index, $g_index, $b_index)]->hit_count > $local_hit_count) {
                  // Neighbour hit count is higher, so this is NOT a local maximum.
                  $is_local_maximum = FALSE;
                  // Break inner loop.
                  break;
                }
              }
            }
          }
          // If this is not a local maximum, continue with loop.
          if ($is_local_maximum === FALSE) {
            continue;
          }
          // Otherwise, add this cell as local maximum.
          $avg_r = $this->cells[$local_index]->r_acc / floatval($this->cells[$local_index]->hit_count);
          $avg_g = $this->cells[$local_index]->g_acc / floatval($this->cells[$local_index]->hit_count);
          $avg_b = $this->cells[$local_index]->b_acc / floatval($this->cells[$local_index]->hit_count);
          array_push($local_maxima, new LocalMaximum($local_hit_count, $local_index, $avg_r, $avg_g, $avg_b));
        }
      }
    }
    // Return local maxima sorted with respect to hit count.
    usort($local_maxima, function ($left, $right) {
      return $right->hit_count <=> $left->hit_count;
    });

    return $local_maxima;
  }

  public function filter_distinct_maxima($maxima): array
  {
    // Returns a filtered version of the specified array of maxima,
    // in which all entries have a minimum distance of $this.distinct_threshold.
    $result = [];
    // Check for each maximum.
    foreach ($maxima as $m) {
      // This color is distinct until a color from before is too close.
      $is_distinct = TRUE;
      foreach ($result as $n) {
        // Compute delta components.
        $r_delta = $m->r - $n->r;
        $g_delta = $m->g - $n->g;
        $b_delta = $m->b - $n->b;
        // Compute delta in color space distance.
        $delta = sqrt($r_delta * $r_delta + $g_delta * $g_delta + $b_delta * $b_delta);
        // If too close mark as non-distinct and break inner loop.
        if ($delta < $this->distinct_threshold) {
          $is_distinct = FALSE;
          break;
        }
      }
      // Add to filtered array if is distinct.
      if ($is_distinct === TRUE) {
        array_push($result, $m);
      }
    }

    return $result;
  }

  public function filter_too_similar($maxima): array
  {
    // Returns a filtered version of the specified array of maxima,
    // in which all entries are far enough away from the specified avoid_color.
    $result = [];
    $ar = floatval($this->avoid_color[0]) / 255.0;
    $ag = floatval($this->avoid_color[1]) / 255.0;
    $ab = floatval($this->avoid_color[2]) / 255.0;
    // Check for each maximum.
    foreach ($maxima as $m) {
      // Compute delta components.
      $r_delta = $m->r - $ar;
      $g_delta = $m->g - $ag;
      $b_delta = $m->b - $ab;
      // Compute delta in color space distance.
      $delta = sqrt($r_delta * $r_delta + $g_delta * $g_delta + $b_delta * $b_delta);
      if ($delta >= 0.5) {
        array_push($result, $m);
      }
    }

    return $result;
  }
}
