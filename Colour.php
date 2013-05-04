<?php
/* SVN FILE: $Id: Colour.php 33 2011-08-25 16:49:36Z cyates $*/
/**
* Colour class file.
* @author      Chris Yates <chris.l.yates@gmail.com>
* @copyright   Copyright (c) 2011 PBM Web Development
*/
/**
* Colour class.
* An object representing a CSS colour.
*
* A colour may be represented internally as RGBA, HSLA, or both. It is
* originally represented as whatever its input is; if it’s created with RGB
* values, it’s represented as RGBA, and if it’s created with HSL values, it’s
* represented as HSLA. Once a property is accessed that requires the other
* representation – for example, Colour::red for an HSL color – that
* component is calculated and cached.
*
* The alpha channel of a colour is independent of its RGB or HSL representation.
* It’s always stored, as 1 if nothing else is specified. If only the alpha
* channel is modified using Colour::with(), the cached RGB and HSL values
* are retained.
*
* Colour operations are all piecewise, e.g. when adding two colours each
* component is added independantly; R = R1 + R2, G = G1 + G2, B = B1 + B2.
*
* Colours can be returned as rgb(r, g, b), rgba(r, g, b, a), hsl(h, s, l), hsla(h, s, l, a),
* named colours (CSS3 or HTML4), hex triplets (#rrggbb) with the option for
* short-hand triplets (#rgb) where possible; hex triplets is the fallback for
* named colours.
*/
class Colour
{
  /**@#+
  * Regexes for matching and extracting colours
  */
  const MATCH = '/^((#([\da-f]{6}|[\da-f]{3}))|transparent|{CSS_COLOURS})/';
  const EXTRACT_3 = '/#([\da-f])([\da-f])([\da-f])/';
  const EXTRACT_6 = '/#([\da-f]{2})([\da-f]{2})([\da-f]{2})/';
  /**@#-*/
  const TRANSPARENT = 'transparent';
  const DECREASE = false;
  const INCREASE = true;

  protected $lang='en'; // English - change this and add the messages to translate exceptions

  /**@#-*/

  /**@#-*/
  static private $svgColours = array(
    'aliceblue'           => '#f0f8ff',
    'antiquewhite'        => '#faebd7',
    'aqua'                => '#00ffff',
    'aquamarine'          => '#7fffd4',
    'azure'               => '#f0ffff',
    'beige'               => '#f5f5dc',
    'bisque'              => '#ffe4c4',
    'black'               => '#000000',
    'blanchedalmond'      => '#ffebcd',
    'blue'                => '#0000ff',
    'blueviolet'          => '#8a2be2',
    'brown'               => '#a52a2a',
    'burlywood'           => '#deb887',
    'cadetblue'           => '#5f9ea0',
    'chartreuse'          => '#7fff00',
    'chocolate'           => '#d2691e',
    'coral'               => '#ff7f50',
    'cornflowerblue'      => '#6495ed',
    'cornsilk'            => '#fff8dc',
    'crimson'             => '#dc143c',
    'cyan'                => '#00ffff',
    'darkblue'            => '#00008b',
    'darkcyan'            => '#008b8b',
    'darkgoldenrod'       => '#b8860b',
    'darkgray'            => '#a9a9a9',
    'darkgreen'           => '#006400',
    'darkgrey'            => '#a9a9a9',
    'darkkhaki'           => '#bdb76b',
    'darkmagenta'         => '#8b008b',
    'darkolivegreen'      => '#556b2f',
    'darkorange'          => '#ff8c00',
    'darkorchid'          => '#9932cc',
    'darkred'             => '#8b0000',
    'darksalmon'          => '#e9967a',
    'darkseagreen'        => '#8fbc8f',
    'darkslateblue'       => '#483d8b',
    'darkslategray'       => '#2f4f4f',
    'darkslategrey'       => '#2f4f4f',
    'darkturquoise'       => '#00ced1',
    'darkviolet'          => '#9400d3',
    'deeppink'            => '#ff1493',
    'deepskyblue'         => '#00bfff',
    'dimgray'             => '#696969',
    'dimgrey'             => '#696969',
    'dodgerblue'          => '#1e90ff',
    'firebrick'           => '#b22222',
    'floralwhite'         => '#fffaf0',
    'forestgreen'         => '#228b22',
    'fuchsia'             => '#ff00ff',
    'gainsboro'           => '#dcdcdc',
    'ghostwhite'          => '#f8f8ff',
    'gold'                => '#ffd700',
    'goldenrod'           => '#daa520',
    'gray'                => '#808080',
    'green'               => '#008000',
    'greenyellow'         => '#adff2f',
    'grey'                => '#808080',
    'honeydew'            => '#f0fff0',
    'hotpink'             => '#ff69b4',
    'indianred'           => '#cd5c5c',
    'indigo'              => '#4b0082',
    'ivory'               => '#fffff0',
    'khaki'               => '#f0e68c',
    'lavender'            => '#e6e6fa',
    'lavenderblush'       => '#fff0f5',
    'lawngreen'           => '#7cfc00',
    'lemonchiffon'        => '#fffacd',
    'lightblue'           => '#add8e6',
    'lightcoral'          => '#f08080',
    'lightcyan'           => '#e0ffff',
    'lightgoldenrodyellow'=> '#fafad2',
    'lightgray'           => '#d3d3d3',
    'lightgreen'          => '#90ee90',
    'lightgrey'           => '#d3d3d3',
    'lightpink'           => '#ffb6c1',
    'lightsalmon'         => '#ffa07a',
    'lightseagreen'       => '#20b2aa',
    'lightskyblue'        => '#87cefa',
    'lightslategray'      => '#778899',
    'lightslategrey'      => '#778899',
    'lightsteelblue'      => '#b0c4de',
    'lightyellow'         => '#ffffe0',
    'lime'                => '#00ff00',
    'limegreen'           => '#32cd32',
    'linen'               => '#faf0e6',
    'magenta'             => '#ff00ff',
    'maroon'              => '#800000',
    'mediumaquamarine'    => '#66cdaa',
    'mediumblue'          => '#0000cd',
    'mediumorchid'        => '#ba55d3',
    'mediumpurple'        => '#9370db',
    'mediumseagreen'      => '#3cb371',
    'mediumslateblue'     => '#7b68ee',
    'mediumspringgreen'   => '#00fa9a',
    'mediumturquoise'     => '#48d1cc',
    'mediumvioletred'     => '#c71585',
    'midnightblue'        => '#191970',
    'mintcream'           => '#f5fffa',
    'mistyrose'           => '#ffe4e1',
    'moccasin'            => '#ffe4b5',
    'navajowhite'         => '#ffdead',
    'navy'                => '#000080',
    'oldlace'             => '#fdf5e6',
    'olive'               => '#808000',
    'olivedrab'           => '#6b8e23',
    'orange'              => '#ffa500',
    'orangered'           => '#ff4500',
    'orchid'              => '#da70d6',
    'palegoldenrod'       => '#eee8aa',
    'palegreen'           => '#98fb98',
    'paleturquoise'       => '#afeeee',
    'palevioletred'       => '#db7093',
    'papayawhip'          => '#ffefd5',
    'peachpuff'           => '#ffdab9',
    'peru'                => '#cd853f',
    'pink'                => '#ffc0cb',
    'plum'                => '#dda0dd',
    'powderblue'          => '#b0e0e6',
    'purple'              => '#800080',
    'red'                 => '#ff0000',
    'rosybrown'           => '#bc8f8f',
    'royalblue'           => '#4169e1',
    'saddlebrown'         => '#8b4513',
    'salmon'              => '#fa8072',
    'sandybrown'          => '#f4a460',
    'seagreen'            => '#2e8b57',
    'seashell'            => '#fff5ee',
    'sienna'              => '#a0522d',
    'silver'              => '#c0c0c0',
    'skyblue'             => '#87ceeb',
    'slateblue'           => '#6a5acd',
    'slategray'           => '#708090',
    'slategrey'           => '#708090',
    'snow'                => '#fffafa',
    'springgreen'         => '#00ff7f',
    'steelblue'           => '#4682b4',
    'tan'                 => '#d2b48c',
    'teal'                => '#008080',
    'thistle'             => '#d8bfd8',
    'tomato'              => '#ff6347',
    'turquoise'           => '#40e0d0',
    'violet'              => '#ee82ee',
    'wheat'               => '#f5deb3',
    'white'               => '#ffffff',
    'whitesmoke'          => '#f5f5f5',
    'yellow'              => '#ffff00',
    'yellowgreen'         => '#9acd32'
  );

  /**
  * @var array reverse array (value=>name) of named SVG1.0 colours
  */
  static private $_svgColours;

  /**
  * @var array reverse array (value=>name) of named HTML4 colours
  */
  static private $_html4Colours = array(
    '#000000'=>'black',
    '#000080'=>'navy',
    '#0000ff'=>'blue',
    '#008000'=>'green',
    '#008080'=>'teal',
    '#00ff00'=>'lime',
    '#00ffff'=>'aqua',
    '#800000'=>'maroon',
    '#800080'=>'purple',
    '#808000'=>'olive',
    '#808080'=>'gray',
    '#c0c0c0'=>'silver',
    '#ff0000'=>'red',
    '#ff00ff'=>'fuchsia',
    '#ffff00'=>'yellow',
    '#ffffff'=>'white',
  );

  static private $regex;

  /**@#+
  * RGB colour components
  */
  /**
  * @var integer red component. 0 - 255
  */
  private $r;
  /**
  * @var integer green component. 0 - 255
  */
  private $g;
  /**
  * @var integer blue component. 0 - 255
  */
  private $b;
  /**@#-*/
  /**@#+
  * HSL colour components
  */
  /**
  * @var float hue component. 0 - 360
  */
  private $h;
  /**
  * @var float saturation component. 0 - 100
  */
  private $s;
  /**
  * @var float lightness component. 0 - 100
  */
  private $l;
  /**@#-*/
  /**
  * @var float alpha component. 0 - 1
  */
  private $a = 1;

  /**
  * Constructs an RGB or HSL colour object, optionally with an alpha channel.
  * RGB values must be between 0 and 255. Saturation and lightness values must
  * be between 0 and 100. The alpha value must be between 0 and 1.
  * The colour can be specified as:
  *  + a string that is an SVG colour or of the form #rgb or #rrggbb
  *  + an array with 'r', 'g', and 'b' keys, and optionally an 'a' key.
  *  + an array with 'h', 's', and 'l' keys, and optionally an 'a' key.
  *  + an array of red, green, and blue values, and optionally an alpha value.
  * @param mixed the colour
  * @return Colour
  */
  public function __construct($colour)
  {
    if (is_string($colour)) {
      $colour = strtolower($colour);
      if ($colour===self::TRANSPARENT) {
        $this->r = 0;
        $this->g = 0;
        $this->b = 0;
        $this->a = 0;
      }
      else {
        if (array_key_exists($colour, self::$svgColours)) {
          $colour = self::$svgColours[$colour];
        }

        if (strlen($colour) == 4) {
          preg_match(self::EXTRACT_3, $colour, $matches);
          for ($i = 1; $i < 4; $i++) {
            $matches[$i] = str_repeat($matches[$i], 2);
          }
        }
        else {
          preg_match(self::EXTRACT_6, $colour, $matches);
        }

        if (empty($matches)) {
          throw new ColourException('{prefix}: Invalid colour', array(
            '{prefix}'=>__CLASS__
          ), $this->lang);
        }

        $this->r = intval($matches[1], 16);
        $this->g = intval($matches[2], 16);
        $this->b = intval($matches[3], 16);
        $this->a = 1;
      }
    }
    elseif (is_array($colour)) {
      $scheme = $this->assertValid($colour);
      if ($scheme == 'rgb') {
        $this->r = $colour['r'];
        $this->g = $colour['g'];
        $this->b = $colour['b'];
        $this->a = (isset($colour['a']) ? $colour['a'] : 1);
      }
      elseif ($scheme == 'hsl') {
        $this->h = $colour['h'];
        $this->s = $colour['s'];
        $this->l = $colour['l'];
        $this->a = (isset($colour['a']) ? $colour['a'] : 1);
      }
      else {
        $this->r = $colour[0];
        $this->g = $colour[1];
        $this->b = $colour[2];
        $this->a = (isset($colour[3]) ? $colour[3] : 1);
      }
    }
    else {
      throw new ColourException('{prefix}: Invalid colour', array(
        '{prefix}'=>__CLASS__
      ), $this->lang);
    }
  }

  public function __get($name)
  {
    $getter = 'get'.ucfirst($name);
    if (method_exists($this, $getter)) {
      return $this->$getter();
    }
  }

  // Getters
  /**
  * Returns the alpha component (opacity) of this colour.
  * @return float the alpha component (opacity) of this colour.
  */
  public function getAlpha()
  {
    return $this->a;
  }

  /**
  * Returns the hue of this colour.
  * @return float the hue of this colour.
  */
  public function getHue()
  {
    if (is_null($this->h)) {
      $this->rgb2hsl();
    }
    return $this->h;
  }

  /**
  * Returns the saturation of this colour.
  * @return float the saturation of this colour.
  */
  public function getSaturation()
  {
    if (is_null($this->s)) {
      $this->rgb2hsl();
    }
    return $this->s;
  }

  /**
  * Returns the lightness of this colour.
  * @return float the lightness of this colour.
  */
  public function getLightness()
  {
    if (is_null($this->l)) {
      $this->rgb2hsl();
    }
    return $this->l;
  }

  /**
  * Returns the blue component of this colour.
  * @return integer the blue component of this colour.
  */
  public function getBlue()
  {
    if (is_null($this->b)) {
      $this->hsl2rgb();
    }
    $component = round(abs($this->b));
    return ($component > 255 ? $component % 255 : $component);
  }

  /**
  * Returns the green component of this colour.
  * @return integer the green component of this colour.
  */
  public function getGreen()
  {
    if (is_null($this->g)) {
      $this->hsl2rgb();
    }
    $component = round(abs($this->g));
    return ($component > 255 ? $component % 255 : $component);
  }

  /**
  * Returns the red component of this colour.
  * @return integer the red component of this colour.
  */
  public function getRed()
  {
    if (is_null($this->r)) {
      $this->hsl2rgb();
    }
    $component = round(abs($this->r));
    return ($component > 255 ? $component % 255 : $component);
  }

  /**
  * Returns an array with the RGB components of this colour.
  * @return array the RGB components of this colour
  */
  public function getRgb()
  {
    return array($this->getRed(), $this->getGreen(), $this->getBlue());
  }

  /**
  * Returns an array with the RGB and alpha components of this colour.
  * @return array the RGB and alpha components of this colour
  */
  public function getRgba()
  {
    return array($this->getRed(), $this->getGreen(), $this->getBlue(), $this->a);
  }

  /**
  * Returns an array with the HSL components of this colour.
  * @return array the HSL components of this colour
  */
  public function getHsl()
  {
    return array($this->getHue(), $this->getSaturation(), $this->getLightness());
  }

  /**
  * Returns an array with the HSL and alpha components of this colour.
  * @return array the HSL and alpha components of this colour
  */
  public function getHsla()
  {
    return array($this->getHue(), $this->getSaturation(), $this->getLightness(), $this->a);
  }

  /**
  * Returns whether this colour object is translucent; that is, whether the alpha channel is non-1.
  * @return boolean true if this colour is translucent, false if not
  */
  public function isTranslucent()
  {
    return $this->a < 1;
  }

  /**
  * Converts the colour to a string.
  * @param string The format:
  *   rgb:  rgb(#r, #g, #b)
  *   rgba: rgb(#r, #g, #b, a)
  *   hsl:  hsl(h, s, l)
  *   hsla: hsla(h, s, l, a)
  *   hex:  #rrggbb; #rgb where possible if $short==true
  *   named: CSS3 SVG1.0 colour names if $css3==true, HTML4 colour names if not,
  *   or #rrggbb if the colour is not named; #rgb where possible if $short==true.
  * @param boolean whether to use CSS3 SVG1.0 colour names
  * @param boolean whether to use shorthand hex colours
  * @return string the colour in the required format
  */
  public function toString($format = 'rgba', $css3 = true, $short = true)
  {
    switch($format) {
      case 'rgb':
        $rgb = $this->getRgb();
        return sprintf('rgb(%d, %d, %d)', $rgb[0], $rgb[1], $rgb[2]);
        break;
      case 'rgba':
        $rgba = $this->getRgba();
        return sprintf('rgba(%d, %d, %d, %1.2f)', $rgba[0], $rgba[1], $rgba[2], $rgba[3]);
        break;
      case 'hsl':
        $hsl = $this->getHsl();
        return sprintf('hsl(%d, %d, %d)', $hsl[0], $hsl[1], $hsl[2]);
        break;
      case 'hsla':
        $hsla = $this->getHsla();
        return sprintf('hsla(%d, %d, %d, %1.2f)', $hsla[0], $hsla[1], $hsla[2], $hsla[3]);
        break;
      case 'hex':
        return  $this->hex($this->getRgb(), $short);
        break;
      case 'named':
        $rgb = $this->getRgb();
        $hex = $this->hex($rgb);
        if ($css3) {
          if (empty(self::$_svgColours)) {
            self::$_svgColours = array_flip(self::$svgColours);
          }
          return (array_key_exists($hex, self::$svgColours)
            ?self::$_svgColours[$hex]
            :($short ?$this->hex($rgb, $short) :$hex)
          );
        }
        else {
          return (array_key_exists($hex, self::$_html4Colours)
            ?self::$_html4Colours[$hex]
            :($short ?$this->hex($rgb, $short) :$hex)
          );
        }
        return ($short ?$this->hex($rgb, $short) :$hex);
        break;
      default:
        throw new ColourException('{prefix}: Invalid format "{format}"', array(
          '{prefix}'=>__METHOD__,
          '{format}'=>$format)
        ), $this->lang);
        break;
    }
  }

  private function hex($rgb, $short = false)
  {
    $hex = sprintf('#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
    return ($short && $hex[1]===$hex[2] && $hex[3]===$hex[4] && $hex[5]===$hex[6]
      ?'#'.$hex[1].$hex[3].$hex[5]
      :$hex
    );
  }

  // Colour arithmetic

  /**
  * Colour addition
  * @param mixed Colour or integer to add
  * @return Colour The resulting colour
  */
  public function add($other)
  {
    if ($other instanceof Colour) {
      $this->r = $this->getRed()   + $other->getRed();
      $this->g = $this->getGreen() + $other->getGreen();
      $this->b = $this->getBlue()  + $other->getBlue();
    }
    elseif (is_int($other)) {
      $this->r = $this->getRed()   + $other;
      $this->g = $this->getGreen() + $other;
      $this->b = $this->getBlue()  + $other;
    }
    else {
      throw new ColourException('{prefix}: Invalid operand', array(
        '{prefix}'=>__METHOD__
      ), $this->lang);
    }
    return $this;
  }

  /**
  * Colour subraction
  * @param mixed Colour integer to subtract
  * @return Colour The resulting colour
  */
  public function subtract($other)
  {
    if ($other instanceof Colour) {
      $this->r = $this->getRed()   - $other->getRed();
      $this->g = $this->getGreen() - $other->getGreen();
      $this->b = $this->getBlue()  - $other->getBlue();
    }
    elseif (is_int($other)) {
      $this->r = $this->getRed()   - $other;
      $this->g = $this->getGreen() - $other;
      $this->b = $this->getBlue()  - $other;
    }
    else {
      throw new ColourException('{prefix}: Invalid operand', array(
        '{prefix}'=>__METHOD__
      ), $this->lang);
    }
    return $this;
  }

  /**
  * Colour multiplication
  * @param mixed Colour or integer to multiply by
  * @return Colour The resulting colour
  */
  public function multiply($other)
  {
    if ($other instanceof Colour) {
      $this->r = $this->getRed()   * $other->getRed();
      $this->g = $this->getGreen() * $other->getGreen();
      $this->b = $this->getBlue()  * $other->getBlue();
    }
    elseif (is_int($other)) {
      $this->r = $this->getRed()   * $other;
      $this->g = $this->getGreen() * $other;
      $this->b = $this->getBlue()  * $other;
    }
    else {
      throw new ColourException('{prefix}: Invalid operand', array(
        '{prefix}'=>__METHOD__
      ), $this->lang);
    }
    return $this;
  }

  /**
  * Colour division
  * @param mixed Colour or integer to divide by
  * @return Colour The resulting colour
  */
  public function divide($other)
  {
    if ($other instanceof Colour) {
      $this->r = $this->getRed()   / $other->getRed();
      $this->g = $this->getGreen() / $other->getGreen();
      $this->b = $this->getBlue()  / $other->getBlue();
    }
    elseif (is_int($other)) {
      $this->r = $this->getRed()   / $other;
      $this->g = $this->getGreen() / $other;
      $this->b = $this->getBlue()  / $other;
    }
    else {
      throw new ColourException('{prefix}: Invalid operand', array(
        '{prefix}'=>__METHOD__
      ), $this->lang);
    }
    return $this;
  }

  /**
  * Colour modulus
  * @param mixed value (Colour or integer) to divide by
  * @return Colour The resulting colour
  */
  public function modulus($other)
  {
    if ($other instanceof Colour) {
      $this->r = $this->getRed()   % $other->getRed();
      $this->g = $this->getGreen() % $other->getGreen();
      $this->b = $this->getBlue()  % $other->getBlue();
    }
    elseif (is_int($other)) {
      $this->r = $this->getRed()   % $other;
      $this->g = $this->getGreen() % $other;
      $this->b = $this->getBlue()  % $other;
    }
    else {
      throw new ColourException('{prefix}: Invalid operand', array(
        '{prefix}'=>__METHOD__
      ), $this->lang);
    }
    return $this;
  }

  /**
  * Colour bitwise AND
  * @param mixed value (Colour or integer) to bitwise AND with
  * @return Colour The resulting colour
  */
  public function bwAnd($other)
  {
    if ($other instanceof Colour) {
      $this->r = $this->getRed()   & $other->getRed();
      $this->g = $this->getGreen() & $other->getGreen();
      $this->b = $this->getBlue()  & $other->getBlue();
    }
    elseif (is_int($other)) {
      $this->r = $this->getRed()   & $other;
      $this->g = $this->getGreen() & $other;
      $this->b = $this->getBlue()  & $other;
    }
    else {
      throw new ColourException('{prefix}: Invalid operand', array(
        '{prefix}'=>__METHOD__
      ), $this->lang);
    }
    return $this;
  }

  /**
  * Colour bitwise OR
  * @param mixed value (Colour or integer) to bitwise OR with
  * @return Colour the colour result
  */
  public function bwOr($other)
  {
    if ($other instanceof Colour) {
      $this->r = $this->getRed()   | $other->getRed();
      $this->g = $this->getGreen() | $other->getGreen();
      $this->b = $this->getBlue()  | $other->getBlue();
    }
    elseif (is_int($other)) {
      $this->r = $this->getRed()   | $other;
      $this->g = $this->getGreen() | $other;
      $this->b = $this->getBlue()  | $other;
    }
    else {
      throw new ColourException('{prefix}: Invalid operand', array(
        '{prefix}'=>__METHOD__
      ), $this->lang);
    }
    return $this;
  }

  /**
  * Colour bitwise XOR
  * @param mixed value (Colour or integer) to bitwise XOR with
  * @return Colour the colour result
  */
  public function bwXor($other)
  {
    if ($other instanceof Colour) {
      $this->r = $this->getRed()   ^ $other->getRed();
      $this->g = $this->getGreen() ^ $other->getGreen();
      $this->b = $this->getBlue()  ^ $other->getBlue();
    }
    elseif (is_int($other)) {
      $this->r = $this->getRed()   ^ $other;
      $this->g = $this->getGreen() ^ $other;
      $this->b = $this->getBlue()  ^ $other;
    }
    else {
      throw new ColourException('{prefix}: Invalid operand', array(
        '{prefix}'=>__METHOD__
      ), $this->lang);
    }
    return $this;
  }

  /**
  * Colour bitwise NOT
  * @return Colour the colour result
  */
  public function bwNot()
  {
    $this->r = ~$this->getRed();
    $this->g = ~$this->getGreen();
    $this->b = ~$this->getBlue();
    return $this;
  }

  /**
  * Colour bitwise Shift Left
  * @param er amount to shift left by
  * @return Colour the colour result
  */
  public function shiftl($other)
  {
    if (is_int($other)) {
      $this->r = $this->getRed()   << $other->value;
      $this->g = $this->getGreen() << $other->value;
      $this->b = $this->getBlue()  << $other->value;
    }
    else {
      throw new ColourException('{prefix}: Invalid operand', array(
        '{prefix}'=>__METHOD__
      ), $this->lang);
    }
    return $this;
  }

  /**
  * Colour bitwise Shift Right
  * @param integer amount to shift right by
  * @return Colour the colour result
  */
  public function shiftr($other)
  {
    if (is_int($other)) {
      $this->r = $this->getRed()   >> $other->value;
      $this->g = $this->getGreen() >> $other->value;
      $this->b = $this->getBlue()  >> $other->value;
    }
    else {
      throw new ColourException('{prefix}: Invalid operand', array(
        '{prefix}'=>__METHOD__
      ), $this->lang);
    }
    return $this;
  }

  // Colour adjustments

  /**
  * Changes the hue of a colour while retaining the lightness and saturation.
  * @param Colour The colour to adjust
  * @param integer The amount to adjust the colour by
  * @return Colour The adjusted colour
  */
  public function adjustHue($degrees)
  {
    return $this->with(array('h'=>(($this->getHue() + $degrees)%360)));
  }

  /**
  * Makes a colour lighter.
  * @param integer The percentage to lighten the colour by
  * @param boolean Whether the amount is a proportion of the current value
  * (true) or the total range (false).
  * The default is false - the amount is a proportion of the total range.
  * If the colour lightness value is 40% and the amount is 50%,
  * the resulting colour lightness value is 90% if the amount is a proportion
  * of the total range, whereas it is 60% if the amount is a proportion of the
  * current value.
  * @return Colour The lightened colour
  */
  public function lighten($amount, $ofCurrent = false)
  {
    return $this->adjust($amount, $ofCurrent, 'l', self::INCREASE, 0, 100);
  }

  /**
  * Makes a colour darker.
  * @param integer The percentage to darken the colour by
  * @param boolean Whether the amount is a proportion of the current value
  * (true) or the total range (false).
  * The default is false - the amount is a proportion of the total range.
  * If the colour lightness value is 80% and the amount is 50%,
  * the resulting colour lightness value is 30% if the amount is a proportion
  * of the total range, whereas it is 40% if the amount is a proportion of the
  * current value.
  * @return Colour The darkened colour
  */
  public function darken($amount, $ofCurrent = false)
  {
    return $this->adjust($amount, $ofCurrent, 'l', self::DECREASE, 0, 100);
  }

  /**
  * Makes a colour more saturated.
  * @param integer The percentage to saturate the colour by
  * @param boolean Whether the amount is a proportion of the current value
  * (true) or the total range (false).
  * The default is false - the amount is a proportion of the total range.
  * If the colour saturation value is 40% and the amount is 50%,
  * the resulting colour saturation value is 90% if the amount is a proportion
  * of the total range, whereas it is 60% if the amount is a proportion of the
  * current value.
  * @return Colour The saturated colour
  */
  public function saturate($amount, $ofCurrent = false)
  {
    return $this->adjust($amount, $ofCurrent, 's', self::INCREASE, 0, 100);
  }

  /**
  * Makes a colour less saturated.
  * @param integer The percentage to desaturate the colour by
  * @param boolean Whether the amount is a proportion of the current value
  * (true) or the total range (false).
  * The default is false - the amount is a proportion of the total range.
  * If the colour saturation value is 80% and the amount is 50%,
  * the resulting colour saturation value is 30% if the amount is a proportion
  * of the total range, whereas it is 40% if the amount is a proportion of the
  * current value.
  * @return Colour The desaturateed colour
  */
  public function desaturate($amount, $ofCurrent = false)
  {
    return $this->adjust($amount, $ofCurrent, 's', self::DECREASE, 0, 100);
  }

  /**
  * Makes a colour more opaque.
  * @param integer The amount to opacify the colour by
  * If the amount is between 0 and 1 the adjustment is absolute.
  * If the amount>1 it is a percentage and the adjustment is relative.
  * If the colour alpha value is 0.4
  * if the amount is 0.5 the resulting colour alpha value  is 0.9,
  * whereas if the amount is 50 the resulting colour alpha value  is 0.6.
  * @return Colour The opacified colour
  */
  public function opacify($amount, $ofCurrent = false)
  {
    return $this->adjust($amount, $ofCurrent, 'a', self::INCREASE, 0, ($amount>1
      ? 100 : 1
    ));
  }

  /**
  * Makes a colour more transparent.
  * @param integer The amount to transparentise the colour by
  * If the amount is between 0 and 1 the adjustment is absolute.
  * If the amount>1 it is a percentage and the adjustment is relative.
  * If the colour alpha value is 0.8
  * if the amount is 0.5 the resulting colour alpha value  is 0.3,
  * whereas if the amount is 50% the resulting colour alpha value  is 0.4.
  * @return Colour The transparentised colour
  */
  public function transparentise($amount, $ofCurrent = false)
  {
    return $this->adjust($amount, $ofCurrent, 'a', self::DECREASE, 0, ($amount>1
      ? 100 : 1
    ));
  }

  /**
  * Makes a colour more transparent.
  * @param integer The amount to transparentize the colour by
  * If the amount is between 0 and 1 the adjustment is absolute.
  * If the amount>1 it is a percentage and the adjustment is relative.
  * If the colour alpha value is 0.8
  * if the amount is 0.5 the resulting colour alpha value  is 0.3,
  * whereas if the amount is 50% the resulting colour alpha value  is 0.4.
  * @return Colour The transparentized colour
  */
  public function transparentize($amount, $ofCurrent = false)
  {
    return $this->adjust($amount, $ofCurrent, 'a', self::DECREASE, 0, ($amount>1
      ? 100 : 1
    ));
  }

  /**
  * Makes a colour more opaque.
  * Alias for {@link opacify}.
  * @param integer The amount to opacify the colour by
  * @param boolean Whether the amount is a proportion of the current value
  * (true) or the total range (false).
  * @return Colour The opacified colour
  * @see opacify
  */
  public function fade_in($amount, $ofCurrent = false)
  {
    return $this->opacify($amount, $ofCurrent);
  }

  /**
  * Makes a colour more transparent.
  * Alias for {@link transparentize}.
  * @param integer The amount to transparentize the colour by
  * @param boolean Whether the amount is a proportion of the current value
  * (true) or the total range (false).
  * @return Colour The transparentized colour
  * @see transparentise
  */
  public function fade_out($amount, $ofCurrent = false)
  {
    return $this->transparentise($amount, $ofCurrent);
  }

  /**
  * Returns the complement of a colour.
  * Rotates the hue by 180 degrees.
  * @return Colour The comlemented colour
  * @uses adjust_hue()
  */
  public function complement()
  {
    return $this->adjustHue(180);
  }

  /**
  * Greyscale for American speakers.
  * @return Colour The greyscale colour
  * @see desaturate
  */
  public function grayscale()
  {
    return $this->desaturate(100);
  }

  /**
  * Converts a colour to greyscale.
  * Reduces the saturation to zero.
  * @return Colour The greyscale colour
  * @see desaturate
  */
  public function greyscale()
  {
    return $this->desaturate(100);
  }

  /**
  * Mixes two colours together.
  * Takes the average of each of the RGB components, optionally weighted by the
  * given percentage. The opacity of the colours is also considered when
  * weighting the components.
  * The weight specifies the amount of the first colour that should be included
  * in the returned colour. The default, 50%, means that half the first colour
  * and half the second colour should be used. 25% means that a quarter of the
  * first colour and three quarters of the second colour should be used.
  * For example:
  *   mix(#f00, #00f)=>#7f007f
  *   mix(#f00, #00f, 25%)=>#3f00bf
  *   mix(rgba(255, 0, 0, 0.5), #00f)=>rgba(63, 0, 191, 0.75)
  *
  * @param Colour The colour to mix
  * @param float Percentage of the first colour to use
  * @return Colour The mixed colour
  */
  public function mix($other, $weight = null)
  {
    if (!$other instanceof Colour) $other = new Colour($other);
    if (is_null($weight)) $weight = 50;
    if (!is_int($weight)) {
      throw new ColourException('{prefix}: Weight must be an integer', array(
        '{prefix}'=>__METHOD__
      ), $this->lang);
    }

    if (!$this->assertInRange($weight, 0, 100)) {
      throw new ColourException('{prefix}: Weight must be between 0 and 100', array(
        '{prefix}'=>__METHOD__
      ), $this->lang);
    }

    /*
    * This algorithm factors in both the user-provided weight
    * and the difference between the alpha values of the two colours
    * to decide how to perform the weighted average of the two RGB values.
    *
    * It works by first normalizing both parameters to be within [-1, 1],
    * where 1 indicates "only use this colour", -1 indicates "only use the other
    * colour", and all values in between indicated a proportionately weighted
    * average.
    *
    * Once we have the normalized variables w and a,
    * we apply the formula (w + a)/(1 + w*a)
    * to get the combined weight (in [-1, 1]) of colour1.
    * This formula has two especially nice properties:
    *
    ** When either w or a are -1 or 1, the combined weight is also that number
    *  (cases where w* a == -1 are undefined, and handled as a special case).
    *
    ** When a is 0, the combined weight is w, and vice versa
    *
    * Finally, the weight of this colour is renormalized to be within [0, 1]
    * and the weight of the other colour is given by 1 minus the weight of this
    * colour.
    */

    $p = $weight / 100;
    $w = $p * 2 - 1;
    $a = $this->a - $other->alpha;

    $w1 = ((($w * $a == -1) ?$w :($w + $a) / (1 + $w * $a)) + 1) / 2;
    $w2 = 1 - $w1;

    $rgb1 = $this->rgb();
    $rgb2 = $other->rgb();
    $rgba = array();

    foreach ($rgb1 as $key=>$value) {
      $rgba[$key] = $value * $w1 + $rgb2[$key] * $w2;
    }

    $rgba[] = $this->a * $p + $other->alpha * (1 - $p);
    return new Colour($rgba);
  }

  /**
  * Returns a copy of this colour with one or more channels changed.
  * RGB or HSL attributes may be changed, but not both at once.
  * @param array attributes to change
  */
  public function with($attributes)
  {
    if ($this->assertValid($attributes, false)==='hsl') {
      $colour = array_merge(array(
        'h'=>$this->getHue(),
        's'=>$this->getSaturation(),
        'l'=>$this->getLightness(),
        'a'=>$this->a
      ), $attributes);
    }
    else {
      $colour = array_merge(array(
        'r'=>$this->getRed(),
        'g'=>$this->getGreen(),
        'b'=>$this->getBlue(),
        'a'=>$this->a
      ), $attributes);
    }
    return new Colour($colour);
  }

  /**
  * Generate a palette of colours based on this colour
  * @param integer The number of colours to produce
  * @param mixed The mode used to generate colours; this determines the number
  * of hues and hence the maximum number of colours. Hue and lightness of the
  * base colour are adjusted to produce new colours; saturation is unchanged.
  * Can be:
  * integer: The number of hues to use; must be a factor of 360 - i.e. an integer that
  * leaves no remainder when 360 is divided by it.
  * array: an array of colours to use.
  * string: one of the built-in schemes:
  * analogous: This colour and the two hues 30 degrees either side.
  * complements: This colour and its complement.
  * mono: The base hue is used and the lightness adjusted in 5% steps.
  * split-complements: This colour and the two hues 150 degrees either side.
  * tetrads: This colour and 3 others with hues at 90 degree intervals (equivalent of $mode=4).
  * triads: This colour and hues at 120 degree intervals (equivalent of $mode=3).
  * The maximum number of colours is 21, including balck and white.
  * wheel: This colour and 11 others with hues at 30 degree intervals (equivalent of $mode=12).
  * If more colours are specifed the lightness is adjusted in 10% steps (except mono).
  * If more colours than the mode can provide are requested an exception is thrown.
  * @param string Colour format: named, hex, rgb, rgba, hsl, hsla
  * @return array Colours in the required format
  */
  public function palette($n, $mode = 'wheel', $format = 'hex')
  {
    $darker = $lighter = array();
    $palette = array();

    if ($mode==='mono') {
      $max = 21;
      if ($n>$max)  {
        throw new ColourException('{prefix}: Maximum number of colours for {mode} mode is {max}', array(
        '{prefix}'=>__METHOD__,
        '{mode}'=>$mode,
        '{max}'=>$max
      ), $this->lang);
    }
      for ($i = 1, $c = 1; $c<$n; $i++) {
        if (($this->getLightness() - $i * 5)>=0) {
          $darker[]  = $this->darken($i * 5);
          $c++;
        }
        if (($this->getLightness() + $i * 5)<=100) {
          $lighter[] = $this->lighten($i * 5);
          $c++;
        }
      }
      $colours = array_merge(array_reverse($darker), array($this), $lighter);
    }
    else {
      $colours = array();
      switch (strtolower($mode)) {
        case 'analogous':
          $colour = $this->adjustHue(-30);
          for ($i = 0; $i<3 && $i<$n; $i++) {
            $colours[] = $colour->adjustHue($i * 30);
          }
          break;
        case 'complements':
          for ($i = 0; $i<2 && $i<$n; $i++) {
            $colours[] = $this->adjustHue($i * 180);
          }
          break;
        case 'split-complements':
          $colour = $this->adjustHue(-150);
          for ($i = 0; $i<3 && $i<$n; $i++) {
            $colours[] = $colour->adjustHue($i * 150);
          }
          break;
        case 'tetrads':
          for ($i = 0; $i<4 && $i<$n; $i++) {
            $colours[] = $this->adjustHue($i * 90);
          }
          break;
        case 'triads':
          for ($i = 0; $i<3 && $i<$n; $i++) {
            $colours[] = $this->adjustHue($i * 120);
          }
          break;
        case 'wheel':
          for ($i = 0; $i<12 && $i<$n; $i++) {
            $colours[] = $this->adjustHue($i * 30);
          }
          break;
        default:
          if (is_int($mode) && (360 % $mode)===0) {
            $adjustment = 360 / $mode;
            for ($i = 0; $i<$mode && $i<$n; $i++) {
              $colours[] = $this->adjustHue($i * $adjustment);
            }
          }
          elseif (is_array($mode)) {
            $class = get_class($this);
            foreach ($mode as $colour) {
              $colours[] = new $class($colour);
            }
          }
          else {
            throw new ColourException('{prefix}: Invalid mode: "{mode}"', array(
              '{prefix}'=>__METHOD__,
              '{mode}'=>$mode
            ), $this->lang);
          }
          break;
      }
      $c = $hues = count($colours);
      $max = $c * 9;
      if ($n>$max) {
        throw new ColourException('{prefix}: Maximum number of colours for {mode} mode is {max}', array(
          '{prefix}'=>__METHOD__,
          '{mode}'=>$mode,
          '{max}'=>$max
        ), $this->lang);
      }
      if ($n>$c) {
        for ($i = 0; $c<$n; $i++) {
          $l = $colours[0]->getLightness();
          $adjustment = ($i + 1) * 10;
          $darker[$i] = $lighter[$i] = array();
          for ($j = 0; $j<$hues && $c<$n; $j++) {
            if ($l - $adjustment>0) {
              $darker[$i][$j]  = $colours[$j]->darken($adjustment);
              $c++;
            }
          }
          for ($j=0; $j<$hues && $c<$n; $j++) {
            if ($l + $adjustment < 100) {
              $lighter[$i][$j] = $colours[$j]->lighten($adjustment);
              $c++;
            }
          }
        }
      }

      for ($i=0, $c=count($darker); $i<$c; $i++) {
        if (!empty($darker[$i])) {
          $colours = array_merge($colours, $darker[$i]);
        }
        if (!empty($lighter[$i])) {
          $colours = array_merge($colours, $lighter[$i]);
        }
      }
    }

    foreach (array_slice($colours, 0, $n) as $colour) {
      $palette[] = $colour->toString($format);
    }
    return $palette;
  }

  /**
  * Adjusts the colour
  * @param integer the amount to adust by
  * @param boolean whether the amount is a proportion of the current value or
  * the total range
  * @param string the attribute to adjust
  * @param boolean whether to decrease (false) or increase (true) the value of the attribute
  * @param float minimum value the amount can be
  * @param float maximum value the amount can bemixed
  * @param string amount units
  */
  private function adjust($amount, $ofCurrent, $attribute, $op, $min, $max)
  {
    if (!is_int($amount)) {
      throw new ColourException('{prefix}: Amount must be an integer', array(
        '{prefix}'=>__METHOD__
      ), $this->lang);
    }
    if (!$this->assertInRange($amount, $min, $max)) {
      throw new ColourException('{prefix}: Amount must be between {min} and {max}', array(
        '{prefix}'=>__METHOD__,
        '{min}'=>$min,
        '{max}'=>$max
      ), $this->lang);
    }

    $amount = $amount * (($attribute==='alpha' && $ofCurrent && $amount<=1) ?100 :1);

    return $this->with(array(
      $attribute=>$this->inRange((
        $ofCurrent
          ?$this->$attribute * (1 + ($amount * ($op===self::INCREASE ?1 :-1)) / 100)
          :$this->$attribute + ($amount * ($op===self::INCREASE ?1 :-1))
      ), $min, $max)
    ));
  }

  private function assertInRange($value, $min, $max)
  {
    return ($value>=$min && $value<=$max);
  }

  /**
  * Asserts that the colour space is valid.
  * Returns the name of the colour space: 'rgb' if red, green, or blue keys given;
  * 'hsl' if hue, saturation or lightness keys given; null if a non-associative array
  * @param array the colour to test
  * @param boolean whether all colour space keys must be given
  * @return string name of the colour space
  * @throws ColourException if mixed colour space keys given or not all
  * keys for a colour space are required but not given
  */
  private function assertValid($colour, $all = true)
  {
    if (array_key_exists('r', $colour) || array_key_exists('g', $colour) || array_key_exists('b', $colour)) {
      if (array_key_exists('h', $colour) || array_key_exists('s', $colour) || array_key_exists('l', $colour)) {
        throw new ColourException('{prefix}: Colour cannot have HSL and RGB keys specified', array(
          '{prefix}'=>__METHOD__
        ), $this->lang);
      }
      if ($all && (!array_key_exists('r', $colour) || !array_key_exists('g', $colour) || !array_key_exists('b', $colour))) {
        throw new ColourException('{prefix}: Colour must have all RGB keys specified', array(
          '{prefix}'=>__METHOD__
        ), $this->lang);
      }
      return 'rgb';
    }
    elseif (array_key_exists('h', $colour) || array_key_exists('s', $colour) || array_key_exists('l', $colour)) {
      if ($all && (!array_key_exists('h', $colour) || !array_key_exists('s', $colour) || !array_key_exists('l', $colour))) {
        throw new ColourException('{prefix}: Colour must have all HSL keys specified', array(
          '{prefix}'=>__METHOD__
        ), $this->lang);
      }
      return 'hsl';
    }
    elseif ($all && sizeof($colour) < 3) {
      throw new ColourException('{prefix}: Colour array must have at least 3 elements', array(
        '{prefix}'=>__METHOD__
      ), $this->lang);
    }
  }

  /**
   * Ensures the value is within the given range, clipping it if needed.
   * @param float the value to test
   * @param float the minimum value
   * @param float the maximum value
   * @return the value clipped to the range
   */
   private function inRange($value, $min, $max)
   {
      return ($value<$min ?$min :($value > $max ?$max :$value));
  }

  // Colourspace conversions

  /**
  * Converts from HSL to RGB colourspace
  * Algorithm from the CSS3 spec: {@link http://www.w3.org/TR/css3-color/#hsl-color}
  * @uses hue2rgb()
  */
  private function hsl2rgb() {
    $h = ($this->h % 360) / 360;
    $s = $this->s / 100;
    $l = $this->l / 100;

    $m1 = ($l<=0.5 ?$l * ($s + 1) :$l + $s - $l * $s);
    $m2 = $l * 2 - $m1;

    $this->r = $this->hue2rgb($m1, $m2, $h + 1 / 3);
    $this->g = $this->hue2rgb($m1, $m2, $h);
    $this->b = $this->hue2rgb($m1, $m2, $h - 1 / 3);
  }

  /**
  * Converts from hue to RGB colourspace
  */
  private function hue2rgb($m1, $m2, $h)
  {
    $h += ($h<0 ?1 :($h>1 ?-1 :0));

    if (($h * 6)<1) {
      $c = $m2 + ($m1 - $m2) * $h * 6;
    }
    elseif (($h * 2)<1) {
      $c = $m1;
    }
    elseif (($h * 3)<2) {
      $c = $m2 + ($m1 - $m2) * (2 / 3 - $h) * 6;
    }
    else {
      $c = $m2;
    }
    return $c * 255;
  }

  /**
  * Converts from RGB to HSL colourspace
  * Algorithm adapted from {@link http://en.wikipedia.org/wiki/HSL_and_HSV#Conversion_from_RGB_to_HSL_or_HSV}
  */
  private function rgb2hsl() {
    $rgb = array('r'=>$this->r / 255, 'g'=>$this->g / 255, 'b'=>$this->b / 255);
    $max = max($rgb);
    $min = min($rgb);
    $c = $max - $min;

    // Lightness
    $l = ($max + $min) / 2;
    $this->l = $l * 100;

    // Saturation
    $this->s = ($c ?($l<=0.5 ?$c / (2 * $l) :$c / (2 - 2 * $l)) :0 ) * 100;

    // Hue
    switch($max) {
      case $min:
        $h = 0;
        break;
      case $rgb['r']:
        // Factor things by 100 to deal with fractional numbers; % converts operands to int
        $h = ((($rgb['g'] - $rgb['b']) * 100 / $c) % 600) / 100;
        break;
      case $rgb['g']:
        $h = (($rgb['b'] - $rgb['r']) / $c) + 2;
        break;
      case $rgb['b']:
        $h = (($rgb['r'] - $rgb['g']) / $c) + 4;
        break;
    }
    $this->h = (360 + $h * 60) % 360;
  }
}

/**
* Exception class
* @param string the exception message
* @param array parameters for the message
* @param string the language to translate the message into
*/
class ColourException extends Exception
{
  public function __construct($message, $params, $lang)
  {
    $messages = array(
      'en-us'=>array( // American trnaslation
        '{prefix}: Invalid colour'=>'{prefix}: Invalid color',
        '{prefix}: Maximum number of colours for {mode} mode is {max}'=>'{prefix}: Maximum number of colors for {mode} mode is {max}',
        '{prefix}: Colour cannot have HSL and RGB keys specified'=>'{prefix}: Color cannot have HSL and RGB keys specified',
        '{prefix}: Colour must have all RGB keys specified'=>'{prefix}: Color must have all RGB keys specified',
        '{prefix}: Colour must have all HSL keys specified'=>'{prefix}: Color must have all HSL keys specified',
        '{prefix}: Colour array must have at least 3 elements'=>'{prefix}: Color array must have at least 3 elements',
      ),
      'template'=>array( // list of all exception messages
        '{prefix}: Invalid colour'=>'',
        '{prefix}: Invalid format "{format}"'=>'',
        '{prefix}: Invalid operand'=>'',
        '{prefix}: Weight must be an integer'=>'',
        '{prefix}: Weight must be between 0 and 100'=>'',
        '{prefix}: Maximum number of colours for {mode} mode is {max}'=>'',
        '{prefix}: Invalid mode: "{mode}"'=>'',
        '{prefix}: Amount must be an integer'=>'',
        '{prefix}: Amount must be between {min} and {max}'=>'',
        '{prefix}: Colour cannot have HSL and RGB keys specified'=>'',
        '{prefix}: Colour must have all RGB keys specified'=>'',
        '{prefix}: Colour must have all HSL keys specified'=>'',
        '{prefix}: Colour array must have at least 3 elements'=>'',
      )
    );
    if (isset($messages[$lang][$message])) {
      $message = $messages[$lang][$message];
    }

    $message = strtr($message, $params);
    switch ($lang) {
      case 'en-us': // Throw an American exception
        throw new ColorException($message);
      default: // English
        parent::__construct($message);
    }
  }
}

/**
* Colour class for American speakers
*/
class Color extends Colour
{
  protected $lang = 'en-us'; // American
}
/**
* Exception class for American speakers
*/
class ColorException extends Exception
{
  public function __construct($message)
  {
    $message = str_replace('olour', 'olor', $message);
    parent::__construct($message);
  }
}
