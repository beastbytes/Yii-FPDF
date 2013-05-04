<?php
/**
* FPDFBehavior class file.
* Integrates the {@link http://www.fpdf.org/ FPDF Library}.
*
* @copyright  Copyright © 2011 PBM Web Development - All Rights Reserved
* @version		1.0.0
* @license		BSD 3-Clause
*/
/**
* FPDFBehavior class.
* Features:
* + Full support for the FPDF library
* + UTF-8 support ([tFPDF Class][http://www.fpdf.org/en/script/script92.php])
* + Adds additonal methods to FDPF:
* ++ SetCustomProperty() - for setting PDF document custom properties
* ++ WriteHtml() - for writing HTML snippets. The following tags are supported:
* +++ P, EM, STRONG, H1-H6, A, HR.
* +++ Ordered and unordered lists (not nested) are supported
* +++ There is basic support for tables
* ++ SetHtmlStyles() - defines text, background, and border colours, etc. for tags (see source)
* + Uses "views" in the controller's view path to create PDF documents
* + Support for partial views.
* + View behaviors to provide helper functions and generate headers and footers
*
* Usage:
* Controllers call the generatePDF() (analagous to render()) method to create
* PDF documents.
*
* Views are PHP scripts that call FPDF functions, e.g. $this->Cell(), to create
* the document. Views can call the generatePartial() method to include PDF
* snippets.
*
* Notes:
* + the document creator is set to the application name
* + the view should not call FPDF::Output(); this is done automatically
*/
class FPDFBehavior extends CBehavior
{
  /**
  * @var string Name of the footer callback function
  */
  public $footer = 'Footer';
  /**
  * @var string Name of the header callback function
  */
  public $header = 'Header';
  /**
  * @var array Helper classes
  */
  public $helpers = array();
	/**
	* @var string Path alias to the FPDF library. If null the default is to
	* use the local vendors.fpdf directory
	*/
	public $fpdf;
	/**
	* @var string Default page orientation
	* P or Portrait (default)
  * L or Landscape
  */
	public $orientation = 'P';
  /**
  * @var string Path alias of the directory to store PDFs if dest contains F.
  */
  public $pdfPath = 'application.data.pdf';
  /**
  * @var mixed Page size. Either one of the following:
  * A3
  * A4 (default)
  * A5
  * Letter
  * Legal
  *
  * or:
  * array(width, height) where width and height are expressed in {@link unit}
  */
  public $size = 'A4';
  /**
  * @var string Path alias to the diectory containing TTF fonts.
  * If empty the default directory, path.to.this.file.fonts.unifont, is used
  */
  public $ttfFonts;
	/**
	* @var string Measurment units
	* pt: point
  * mm: millimeter (default)
  * cm: centimeter
  * in: inch
  * (does not include font sizes which are always in pt)
	*/
	public $unit = 'mm';
	/**
	* @var string Path to the view helper.
	* If empty FPDFBehavior will search in the view directory.
	*/
	public $viewHelperPath;

	// The following properties are used when the behavior is an event handler
  /**
  * @var string the destination for the generated PDF. The default is to
  * save to a local file.
  */
  public $dest = 'F';
  /**
  * @var array The events that this behavior responds to.
  */
  public $events = array();
  /**
  * @var string The attribute in the event's sender object used for the
  * filename. This can be a path in dot notation, e.g. relatedModel.attribute
  * The resulting name will be post-fixed with '.pdf'
  */
  public $nameAttribute;
  /**
  * @var string name of the view. If empty the sender's name parameter is
  * used.
  */
  public $view;
  /**
  * @var string path alias to the view directory
  */
  public $viewPath;

	/**
	* @var FPDF The FPDF object
	*/
	private $_pdf;
	/**
	* @var array View data. This used to allow view helpers to access the data
	*/
	private $_data;

  /**
  * Provides access to FPDF methods using $this->fpdfMethod()
  * @param string $name FPDF method
  * @param array $parameters FPDF method parameters
  */
	public function __call($name, $parameters)
  {
		if (method_exists($this->_pdf, $name)) {
			return call_user_func_array(array($this->_pdf, $name), $parameters);
    }
		else {
			return parent::__call($name, $parameters);
    }
	}

	/**
	* Attach the FPDF class
  * @param CComponent $owner The object that the behavior is attached to
	*/
	public function attach($owner)
  {
		parent::attach($owner);

		if (is_string($this->ttfFonts)) {
			define('_SYSTEM_TTFONTS', $this->ttfFonts);
    }
		$path = dirname(__FILE__);
		$alias = md5($path);
		Yii::setPathOfAlias($alias, $path);
		if ($this->fpdf===null) {
			$this->fpdf = "$alias.vendors.tfpdf";
    }
		Yii::import("{$this->fpdf}.tFPDF");
		Yii::import("$alias.*");

		$this->_pdf = new EFPDF($this->orientation, $this->unit, $this->size);
	}

	/**
	* Declares events.
	* All events are handled by the {@link handler} method
	* @return array Events (keys) and the corresponding event handlers (values).
	* @see CBehavior::events
	*/
	public function events()
  {
		return array_fill_keys($this->events, 'handler');
	}

	/**
	* The event handler.
	* @param CEvent $event The event
	*/
	public function handler($event)
  {
		if (!isset($this->nameAttribute)) {
			throw new CException(Yii::t(__CLASS__.__CLASS__, 'nameAttribute property must be set'));
    }
		if (!isset($this->viewPath)) {
			throw new CException(Yii::t(__CLASS__.__CLASS__, 'viewPath property must be set'));
    }
		if (!isset($this->view)&&!isset($event->sender->params['name'])) {
			throw new CException(Yii::t(__CLASS__.__CLASS__, 'view property or event sender name parameter must be set'));
    }

		$this->generatePDF(
			$this->viewPath.(strpos($this->viewPath, '.')!==false?'.':'/')
			.(isset($this->view)?$this->view:$event->sender->params['name']),
			compact('event'),
			CHtml::value($event->sender, $this->nameAttribute).'.pdf',
			$this->dest
		);
	}

	/**
	* Returns the data
	*/
	public function getData()
  {
		return $this->_data;
	}

	/**
	* Generate the PDF document
	* @param string Name of the view to be rendered
	* @param array $data Data for the view
	* @param string The document name. If not specified the document will be sent
	* to the browser (i.e. equavalent to $dest='I') with the name 'doc.pdf'.
  * If 'F' is specified as a destination $name can be a path relative to
  * {@link pdfPath}.
  * $name can be specified without the ".pdf" extension, "doc" and "doc.pdf" are
  * equivalent and valid.
	* @param string $dest Destination of the document. It can take one of the
  * following values:
	* I: send the file inline to the browser; the plug-in is used if available.
	* The name given by $name is used when the user selects the "Save as" option
  * on the link generating the PDF.
	* D: send to the browser and force a file download with the name given by name.
	* S: return the document as a string. $name is ignored unless 'F' is also
  * specified.
	* F: save to a local file with the name given by {@link pdfPath}/name. 'F' may
  * specified with other destinations, e.g. 'FI' saves the PDF locally and sends
  * it to the browser.
	* @return string If $dest contains 'S' the PDF document, else an empty string
	*/
	public function generatePDF($view, $data = null, $name = 'doc.pdf', $dest = 'I')
  {
		if (($viewFile = Yii::app()->getController()->getViewFile($view))===false) {
			throw new CException(Yii::t('yii', '{controller} cannot find the requested view "{view}".',
			array('{controller}'=>get_class(Yii::app()->getController()), '{view}'=>$view)));
    }

    if (strpos($name, '.')===false) {
      $name .= '.pdf';
    }

    $this->beforeGeneratePDF();
		$this->_data = $data;

		$viewHelper = (empty($this->viewHelperPath)
			?str_replace('.php', 'Helper.php', $viewFile)
			:Yii::getPathOfAlias($this->viewHelperPath).'/'.$view.'Helper.php'
		);

		if (file_exists($viewHelper)) {
			$viewHelperPath = dirname($viewHelper);
			$viewHelperAlias = md5($viewHelperPath);
			Yii::setPathOfAlias($viewHelperAlias, $viewHelperPath);
			$this->_pdf->helpers[] = $this->attachBehavior(
				basename($viewFile, '.php'),
				"$viewHelperAlias.".basename($viewHelper, '.php')
			);
		}
    foreach($this->helpers as $n=>$h) {
      $this->_pdf->helpers[] = $this->attachBehavior($n, $h);
    }

		$this->_pdf->header = $this->header;
		$this->_pdf->footer = $this->footer;
		$this->_pdf->SetCreator(Yii::app()->name, true);
		$this->generateInternal($viewFile, $data);

		$dest = strtoupper($dest);
		if (strpos($dest, 'F')!==false) {
			$dest = str_replace('F', '', $dest);
      $pdfPath = Yii::getPathOfAlias($this->pdfPath);

      // append any path in $name to $pdfPath and make $name the filename
      $namePath = explode(DIRECTORY_SEPARATOR, $name);
      $name = array_pop($namePath);
      $pdfPath .= DIRECTORY_SEPARATOR.join(DIRECTORY_SEPARATOR, $namePath);

      if (!file_exists($pdfPath)) {
        mkdir($pdfPath, 0644, true);
      }
			$this->_pdf->Output($pdfPath.DIRECTORY_SEPARATOR.$name, 'F');
		}
		$return = ($dest ?$this->_pdf->Output($name, $dest) :'');

    $this->afterGeneratePDF();

    return $return;

	}

	/**
	 * Generates PDF from a view.
	 *
	 * The named view refers to a PHP script (resolved via CController::getViewFile())
	 * that is included by this method. If $data is an associative array,
	 * it will be extracted as PHP variables and made available to the script.
	 *
	 * This method differs from {@link generatePDF()} in that it does not
	 * output the result. It is used to render a partial view.
	 *
	 * @param string $view Name of the view to be rendered. See CController::getViewFile()
	 * for details of how the view script is resolved.
	 * @param array $data Data to be extracted into PHP variables and made available to
	 * the view script
	 * @throws CException if the view does not exist
	 * @see generatePDF
	 */
	public function generatePartial($view, $data = null)
  {
		if (($viewFile = Yii::app()->getController()->getViewFile($view))===false) {
			throw new CException(Yii::t('yii', '{controller} cannot find the requested view "{view}".',
			array('{controller}'=>get_class(Yii::app()->getController()), '{view}'=>$view)));
    }
		$this->generateInternal($viewFile, $data);
	}

	/**
	 * Generates PDF from a view file.
	 * This method includes the view file as a PHP script
	 * @param string $_viewFile_ view file
	 * @param array $_data_ data to be extracted and made available to the view file
	 */
	public function generateInternal($_viewFile_, $_data_ = null)
  {
		// we use special variable names here to avoid conflict when extracting data
		if (is_array($_data_)) {
			extract($_data_, EXTR_PREFIX_SAME, 'data');
    }
		else {
			$data = $_data_;
    }
		require($_viewFile_);
	}

  /**
   * This method is invoked before the PDF is generated.
   * @return boolean whether the PDF is generated. Defaults to true.
   */
  protected function beforeGeneratePDF()
  {
    if ($this->owner->hasEventHandler('onBeforeGeneratePDF')) {
      $event=new CModelEvent($this);
      $this->owner->onBeforeGeneratePDF($event);
      return $event->isValid;
    }
    else {
      return true;
    }
  }

  /**
   * This method is invoked after the PDF has been generated.
   */
  protected function afterGeneratePDF()
  {
    if ($this->owner->hasEventHandler('onAfterGeneratePDF')) {
      $this->owner->onAfterGeneratePDF(new CModelEvent($this));
    }
  }
}
