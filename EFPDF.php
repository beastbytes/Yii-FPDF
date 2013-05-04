<?php
/**
* EFPDF class file.
* @copyright  Copyright © 2011 PBM Web Development - All Rights Reserved
* @version		1.0.0
* @license		BSD 3-Clause
*/
/**
* EFPDF class.
* Extended FPDF class:
* + Implements the header and footer callback functions for the
* {@link http://www.fpdf.org/ FPDF Library}.
* Adds the following methods:
* + SetCustomProperty() - for setting PDF document custom properties
* + WriteHtml() - for writing HTML snippets. The following tags are supported:
* ++ P, EM, STRONG, H1-H6, A, HR.
* ++ Ordered and unordered lists (not nested) are supported
* ++ There is basic support for tables
* + SetHtmlStyles() - defines text, background, and border colours, etc. for tags (see source)
*/
class EFPDF extends tFPDF
{
  /**
  * @var string Name of the footer callback function
  */
  public $footer;
	/**
	* @var string Name of the header callback function
	*/
	public $header;
  /**
  * @var array PDFHelpers
  */
  public $helpers = array();
	/**
	* @var array HTML entities and their replacements. Used by {@link WriteHtml()}
	*/
	public $htmlEntities = array(
    '&trade;'=>'™',
    '&copy;'=>'©',
    '&reg;'=>'®',
    '&euro;'=>'€',
    '&pound;'=>'£',
    '&yen;'=>'¥',
    '&mdash;'=>'—',
    '&ndash;'=>'–'
	);
	/**
	* @var array Style for certain HTML tags.
	* Applicable tags are: CAPTION, H1 - H6, TD, and TH.
	* Each entry is indexed by tag in UPPERCASE. The value is an array with the
	* keys 'style', 'fontSize', 'textColor', 'fillColor', 'spaceBefore', and
	* 'spaceAfter'.
	* In additional to the tags there is an entry with the key "default"; this
	* contains the default values that the above tags are reset to on closing;
	* the default key does not have the style key - these are all set false.
	* Used by {@link WriteHtml()}
	* @see $_htmlStyles
	*/
	public $htmlStyles= array(
		'CAPTION'=>array(
			'style'=>'B',
			'fontSize'=>10,
			'spaceBefore'=>5,
			'spaceAfter'=>0,
			'lineHeight'=>5
		),
		'TR'=>array(
			'lineHeight'=>5
		),
		'TD'=>array(
			'width'=>60,
			'height'=>8,
			'borderWidth'=>0.3,
			'borderColor'=>0,
			'spaceBefore'=>0,
			'spaceAfter'=>0
		),
		'TH'=>array(
			'style'=>'B',
			'fillColor'=>0,
			'textColor'=>255,
			'spaceBefore'=>0,
			'spaceAfter'=>0
		),
		'H1'=>array(
			'style'=>'B',
			'fontSize'=>16,
			'spaceBefore'=>5,
			'spaceAfter'=>0
		),
		'H2'=>array(
			'style'=>'B',
			'fontSize'=>14,
			'spaceBefore'=>5,
			'spaceAfter'=>0
		),
		'H3'=>array(
			'style'=>'B',
			'fontSize'=>12,
			'spaceBefore'=>5,
			'spaceAfter'=>0
		),
		'H4'=>array(
			'style'=>'BI',
			'fontSize'=>12,
			'spaceBefore'=>5,
			'spaceAfter'=>0
		),
		'H5'=>array(
			'style'=>'BI',
			'fontSize'=>11,
			'spaceBefore'=>5,
			'spaceAfter'=>0
		),
		'H6'=>array(
			'style'=>'BIU',
			'fontSize'=>10,
			'spaceBefore'=>5,
			'spaceAfter'=>0
		),
		'UL'=>array(
			'bullet'=>'  *'
		),
		'LI'=>array(
			'spaceBefore'=>5
		),
		'default'=>array(
			'style'=>'',
			'fontSize'=>10,
			'textColor'=>0,
			'fillColor'=>255,
			'lineHeight'=>5,
			'spaceBefore'=>5,
			'spaceAfter'=>0
		)
	);

	private $B=false;
	private $I=false;
	private $U=false;
	private $HREF='';
	private $ALIGN='';
	private $_tableCell = false;
	private $_border = 0;
	private $_fill = false;
	private $_list;

	private $builtInProperties = array('title', 'author', 'subject', 'keywords', 'creator', 'producer', 'creationdate', 'moddate', 'trapped');
	private $customProperties = array();

	/**
	* Invoked if an unknown method is called.
	* Looks for the method in the attached helpers and calls it if found.
	* Else calls the parent __call() method
	* @param string method name
	* @param mixed method parameters
	*/
	public function __call($name, $parameters)
  {
		foreach ($this->helpers as $helper) {
			if ($helper instanceof PDFHelper && method_exists($helper, $name)) {
				call_user_func_array(array($helper, $name), $parameters);
      }
    }
	}

	/**
	* Overrides the FPDF Header method.
	* Looks for a method with the header callback name in the attached helpers and
	* calls it if found.
	*/
	public function Header()
  {
		foreach ($this->helpers as $helper) {
			if ($helper instanceof PDFHelper && method_exists($helper, $this->header)) {
				call_user_func(array($helper, $this->header));
      }
    }
	}

	/**
	* Overrides the FPDF Footer method.
	* Looks for a method with the footer callback name in the attached helpers and
	* calls it if found.
	*/
	public function Footer()
  {
		foreach ($this->helpers as $helper) {
			if ($helper instanceof PDFHelper && method_exists($helper, $this->footer)) {
				call_user_func(array($helper, $this->footer));
      }
    }
	}

	/**
	* Overrides the parent _putinfo() to allow custom properties to be set
	*/
	public function _putinfo()
  {
		parent::_putinfo();

		if (!empty($this->customProperties)) {
			foreach ($this->customProperties as $name=>$value) {
				$this->_out("/$name ".$this->_textstring($value));
      }
    }
	}

	/**
	* Sets a custom document property or properties.
	* @param mixed Either an array of name=>value pairs, or the name of the property
	* @param string The property value. Ignored if $name is an array
	*/
	public function setCustomProperty($name, $value = '')
  {
		if (is_array($name)) {
			foreach ($name as $key=>$value) {
				$this->setCustomProperty($key, $value);
      }
			return;
		}

		if (in_array(strtolower($name), $this->builtInProperties)) {
			$this->Error("Property $name is a built in property");
    }

		$this->customProperties[$name]=$value;
	}

	/**
	* Defines the styles used by {@link WriteHtml()}
	* @param array HTML styles
	*/
	public function setHtmlStyles($htmlStyles)
  {
		$this->htmlStyles = CMap::mergeArray($this->htmlStyles, $htmlStyles);
	}

	/**
	* Create PDF from HTML.
	* The following tags are supported:
	*	P, EM, STRONG, H1-H6, A, BR, HR.
	*	Ordered and unordered lists (not nested) are supported
	*	Basic support for tables
	* @param string HTML
	*/
  public function WriteHTML($html)
  {
  	static $inlineTags = array('B', 'I', 'EM', 'STRONG');

    //HTML parser
    $html = preg_replace('/\r?\n\s*/', '', $html);
    $html = str_replace(array_keys($this->htmlEntities), array_values($this->htmlEntities), $html);

    $a = preg_split('/<(.*?)>/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    unset($html);
    foreach ($a as $i=>$e) {
    	$e = trim($e);
      if ($i%2) {
        //Tag
        if($e[0]=='/') {
          $this->CloseTag(strtoupper(substr($e, 1)));
        }
        else {
          //Extract properties
          $a2 = explode(' ', $e);
          $tag = strtoupper(array_shift($a2));
          $prop = array('ALIGN'=>'left');
          foreach ($a2 as $v) {
          	if (preg_match('/([^=]*)=["\']?([^"\']*)/', $v, $a3)) {
            	$prop[strtoupper($a3[1])]=$a3[2];
            }
          }
          $this->OpenTag($tag, $prop);
        }
    	}
      elseif ($e!=='') {
	      //Text
	      if ($this->HREF) {
	        $this->PutLink($this->HREF, $e);
        }
	      elseif ($this->_tableCell) {
					if($e==='$nbsp;') {
						$e = '';
          }
					$this->TableCell($this->htmlStyles['TD']['width'], $this->htmlStyles['TD']['height'], $e, $this->_border, '', 'L', $this->_fill);
	      }
	      elseif ($this->ALIGN=='center') {
	        $this->Cell(0, $this->htmlStyles['default']['lineHeight'], $e, 0, 1, 'C');
        }
	      else {
        	if ($i && in_array(strtoupper($a[$i-1]), $inlineTags)) {
        		$e = " $e ";
          }
	        $this->Write($this->htmlStyles['default']['lineHeight'], $e);
	      }
      }
    }
  }

  private function OpenTag($tag, $prop)
  {
  	//Opening tag
  	switch ($tag) {
		case 'B':
		case 'STRONG':
			$this->SetStyle('B', true);
			break;
		case 'I':
		case 'EM':
			$this->SetStyle('I', true);
			break;
		case 'U':
			$this->SetStyle($tag, true);
			break;
    case 'A':
    	$this->HREF=$prop['HREF'];
			break;
    case 'P':
    	$this->Ln($this->htmlStyles['default']['spaceBefore']);
    	$this->ALIGN=$prop['ALIGN'];
			break;
    case 'H1':
    case 'H2':
    case 'H3':
    case 'H4':
    case 'H5':
    case 'H6':
      $this->Ln($this->htmlStyles[$tag]['spaceBefore']);
      $this->setStyles($tag);
      break;
    case 'CAPTION':
    	$this->Ln($this->htmlStyles[$tag]['lineHeight']);
      $this->setStyles($tag);
      break;
    case 'TD':
    case 'TH':
      $this->setStyles($tag);
      $this->_fill = true;
      if (isset($this->htmlStyles['TD']['borderColor'])) {
        $this->_border = 1;
        if (is_int($this->htmlStyles['TD']['borderColor'])) {
        	$this->SetDrawColor($this->htmlStyles['TD']['borderColor']);
        }
        else {
        	$this->SetDrawColor(
        		$this->htmlStyles['TD']['borderColor']['r'],
        		$this->htmlStyles['TD']['borderColor']['g'],
        		$this->htmlStyles['TD']['borderColor']['b']
        	);
        }
			}
      $this->SetLineWidth($this->htmlStyles['TD']['borderWidth']);
      $this->_tableCell = true;
      break;
    case 'BR':
    case 'TR':
    	$this->Ln($this->htmlStyles[$tag]['lineHeight']);
			break;
    case 'TABLE':
    	$this->Ln($this->htmlStyles['default']['lineHeight']);
			break;
    case 'HR':
    	if(!empty($prop['WIDTH'])) {
      	$Width = $prop['WIDTH'];
      }
	    else {
      	$Width = $this->w - $this->lMargin-$this->rMargin;
      }
	    $this->Ln(2);
	    $x = $this->GetX();
	    $y = $this->GetY();
	    $this->SetLineWidth(0.4);
	    $this->Line($x, $y, $x+$Width, $y);
	    $this->SetLineWidth(0.2);
	    $this->Ln(2);
	    break;
	  case 'OL';
	    $this->_list = 1;
	    break;
	  case 'UL';
	    $this->_list = $this->htmlStyles[$tag]['bullet'];
	    break;
	  case 'LI':
      $this->setStyles($tag);
	    $this->Ln($this->htmlStyles[$tag]['spaceBefore']);
	    $this->Write($this->htmlStyles['default']['lineHeight'], (is_int($this->_list)?($this->_list++).'.':$this->_list));
	    $this->SetX($this->GetX()+5);
	    break;
  	}
  }

  private function CloseTag($tag)
  {
  	//Closing tag
  	switch ($tag) {
		case 'B':
		case 'STRONG':
			$this->SetStyle('B', false);
			break;
		case 'I':
		case 'EM':
			$this->SetStyle('I', false);
			break;
		case 'U':
			$this->SetStyle($tag, false);
			break;
    case 'A':
    	$this->HREF='';
			break;
    case 'P':
    	$this->Ln($this->htmlStyles['default']['spaceAfter']);
    	$this->ALIGN='';
			break;
    case 'H1':
    case 'H2':
    case 'H3':
    case 'H4':
    case 'H5':
    case 'H6':
      $this->Ln($this->htmlStyles[$tag]['spaceAfter']);
    case 'CAPTION':
    	$this->ALIGN='';
      $this->SetStyles('default');
			break;
    case 'TABLE':
    	$this->Ln($this->htmlStyles['default']['lineHeight']*2);
			break;
    case 'TH':
    case 'TD':
      $this->_border = 0;
      $this->_fill = false;
      $this->SetStyles('default');
      $this->SetLineWidth(0);
      $this->_tableCell = false;
      break;
    case 'OL':
    case 'UL':
      $this->_list = null;
      break;
  	}
  }

  private function SetStyles($tag)
  {
  	if ($tag==='default') {
			$this->SetStyle('BIU', false);
    }
		elseif (isset($this->htmlStyles[$tag]['style'])) {
			$this->SetStyle($this->htmlStyles[$tag]['style']);
    }
		elseif (isset($this->htmlStyles[$tag]['fontSize'])) {
			$this->SetFontSize($this->htmlStyles[$tag]['fontSize']);
    }

		$this->SetColor($tag, 'text');
		$this->SetColor($tag, 'fill');
	}

	private function SetColor($tag, $what)
  {
		if (!isset($this->htmlStyles[$tag][$what.'Color'])) {
			return;
    }
		$m = 'Set'.ucfirst($what).'Color';
		if (is_int($this->htmlStyles[$tag][$what.'Color'])) {
			$this->$m($this->htmlStyles[$tag][$what.'Color']);
    }
		elseif (is_array($this->htmlStyles[$tag][$what.'Color'])) {
			$this->$m(
				$this->htmlStyles[$tag][$what.'Color']['r'],
				$this->htmlStyles[$tag][$what.'Color']['g'],
				$this->htmlStyles[$tag][$what.'Color']['b']
			);
    }
		elseif (is_string($this->htmlStyles[$tag][$what.'Color'])) {
			$c = trim($str, '#');
			$l = (strlen($c)===3 ?1 :2);
			$this->$m(substr($c, 0, $l), substr($c, $l, $l), substr($c, $l * 2, $l));
		}
  }

  private function SetStyle($tag, $enable = true)
  {
    //Modify style and select corresponding font
    for ($i = 0, $l = strlen($tag); $i<$l; $i++) {
    	$this->{$tag[$i]} = $enable;
    }
    $style='';
    foreach(array('B', 'I', 'U') as $s) {
      if ($this->$s) {
        $style.=$s;
      }
    }
    $this->SetFont('', $style);
  }

  private function TableCell($w, $h, $text, $border = 0, $ln = 0, $align = 'L', $fill = false, $link = '', $valign = 'M')
  {
  	$this->Cell($w, $h, '', $border, 0, 'L', $fill);
  	$this->SetX($this->GetX() - $w + 2);
  	$this->Cell($w-2, 5, $text, 0, 0, $align, false, $link);
  }

  private function PutLink($urll, $txt)
  {
    //Put a hyperlink
    $this->SetTextColor(0, 0, 255);
    $this->SetStyle('U', true);
    $this->Write(5, $txt, $url);
    $this->SetStyle('U', false);
    $this->SetTextColor(0);
  }
}
