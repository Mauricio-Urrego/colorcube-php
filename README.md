# colorcube-php
Dominant color extraction from RGB images—PHP port of Ole Krause-Sparman's algorithm.

## [demo](http://mauricio-urrego.github.io/colorcube-php/)

This is a PHP port of [ColorCube](https://github.com/pixelogik/ColorCube), by Ole Krause-Sparmann. You can find an excellent description of how it works at [that repo](https://github.com/pixelogik/ColorCube)

ColorCube is for dominant color extraction from RGB images. Given an image element, it returns a sorted array of hex colors.

## usage

```php
$cc = new ColorCube( // all arguments are optional; these are the defaults:
  20,   // color-space resolution
  [255, 255, 255], // avoid color
  0.4   // distinctness threshold
  0.2,  // brightness threshold
);
$image = imagecreatefromjpeg($image_url);
$colors = $cc->get_colors($image);
```
