Yii-FPDF
========

Yii Behavior to create PDF documents using the FPDF library

[fpdf]: http://www.fpdf.org/

##Features
+ Full support for the FPDF library
+ UTF-8 support ([tFPDF Class][http://www.fpdf.org/en/script/script92.php])
+ Adds additonal methods to FDPF:
++ SetCustomProperty() - for setting PDF document custom properties
++ WriteHtml() - for writing HTML snippets. The following tags are supported:
+++ P, EM, STRONG, H1-H6, A, HR.
+++ Ordered and unordered lists (not nested) are supported
+++ There is basic support for tables
++ SetHtmlStyles() - defines text, background, and border colours, etc. for tags (see source)
+ Uses normal Yii view locations
+ Support for partial views.
+ View helpers to provide header and footer rendering and helper functions

## Installation
Extract and save the extension's files in your application; typically under the
application.extensions directory.

Download the [FPDF Library][fpdf] and save it in your
application (the default is for the FPDF library to be at
path.vendors.fpdf.FPDF
where "path" is the path to the directory containing FPDFbehavior.pdf).

## Usage
+ Add the behavior to a controller
  If using the default properties:
`
  public function behaviors()
  {
    return array(
      'fpdf'=>'path.to.FPDFbehavior',
      )
    );
  }
`

  or, to change the behavior's properties:
`
  public function behaviors()
  {
    return array(
      'fpdf'=>array(
        'class'=>'path.to.FPDFbehavior',
        'fpdf'=>'path.to.FPDF',
        'behaviors'=>'path.to.view.behaviors',
        'header'=>'headerCallbackFunctionName',
        'footer'=>'footerCallbackFunctionName',
        'orientation'=>'L',
        'unit'=>'pt',
        'size'=>'A3'
      )
    );
  }
`

+ call the generatePDF() method (typically from a controller action, but can be within a view).
+ views to generate PDF documents are in the normal view location and use
[FPDF library][fpdf] methods to create a PDF document; the $this variable
represents the [FPDF library][fpdf], e.g. $this->FPDFMethod() (see the
[FPDF Library][fpdf] Reference Maunual for method details).
+ **Note:** To access the controller in the view use $this->getOwner().

## Helpers
Helpers are classes that contain helper methods that provide commonly used
rendering, e.g. rendering of an address.

Helpers must be extended from the PDFHelper class. Methods in a helper are
called from the view, e.g. $this->viewHelperMethod($param1, $param2, ...).

Helper methods access the [FPDF library][fpdf] methods by $this->FPDFMethod()
and can access view data with $this->>data['dataItem'].

Attach helpers by specifiying them in the FDPF::helpers property as an array of
'name'=>'path.to.helper' pairs. Multiple helpers can be attached; if a method is
called that exists in two or more helpers, the method in the earliest attached
helper is used.

### View Helper
The view helper provides helper methods only for the current view. It is named
for the view by appending "Helper", e.g. if the view name is "myView" the view
helper will be named "myViewHelper". The location of the view helper is defined
by the viewHelperPath property; the default is in  the view directory.
If used with other helpers the view helper is attached first and so its methods
will be called in preference to methods with the same name in other helpers.
**Note:** The view helper is optional; it can be used with other helpers, as the
only helper, or not at all.

### Headers and Footers
The Header() and Footer() methods are called automatically from the
[FPDF library][fpdf] when a page is added in the view. The behavior looks for
methods in attached helpers with the names defined in its header and footer
properties respectively.


The following example illustrates the structure of the controller, view, and a
helper:
`
class OrdersController extends CController
{
  public function behaviors()
  {
    // This example sets the header and footer properties. If the defaults are
    // used then 'fpdf'=>'path.to.FPDFBehavior' can be used
    return array(
      'fpdf'=>array(
        'class'=>'path.to.FPDFBehavior',
        'fpdf'=>'path.to.FPDF.directory',
        'header'=>'invoiceHeader',
        'footer'=>'invoiceFooter'
      )
    );
  }

  public function actionInvoice($orderId)
  {
    $this->generatePDF('invoice', array(
      'order'=>Order::model()->with('customer, lines')->findByPk($orderId),
      'currency' = Yii::app()->params['currency']
    ));
  }
}
`

View (invoice.php in the views.orders directory):
`
$this->AliasNbPages();
$this->SetFont('Arial', '', 10);
$this->AddPage();
$this->Cell(20, 10, $order->number);
$this->setMargins(27, 10, 15);
$this->setY(80);
$this->writeAddress($order->customer->address, 40, 5); // helper method

$invoiceValue = 0;
$nf = Yii::app()->getNumberFormatter();
$this->SetFillColor(0);
$this->SetTextColor(255);
$this->Cell(10, 8, 'Line', 1, 0, 'C', true);
$this->Cell(20, 8, 'Catalogue #', 1, 0, 'C', true);
$this->Cell(40, 8, 'Description', 1, 0, 'C', true);
$this->Cell(20, 8, 'Unit Price', 1, 0, 'C', true);
$this->Cell(10, 8, 'Quantity', 1, 0, 'C', true);
$this->Cell(20, 8, 'Line Value', 1, 0, 'C', true);
$this->SetFillColor(255);
$this->SetTextColor(0);
foreach ($order->lines as $i=>$line) {
  $this->setFillColor($i%2 ?255 :221); // zebra stripe the line items
  $this->Cell(10, 8, $i+1, 1, 0, 'R', true);
  $this->Cell(20, 8, $line->item->number, 1, 0, 'R', true);
  $this->Cell(40, 8, $line->item->description, 1, 0, 'L', true);
  $this->Cell(20, 8, $nf->formatCurrency($line->item->price, $currency), 1, 0, 'R', true);
  $this->Cell(10, 8, $line->quantitiy, 1, 0, 'R', true);
  $lineValue = $line->quantitiy * $line->item->price;
  $this->Cell(20, 8, $nf->formatCurrency($lineValue, $currency), 1, 1, 0, 'R', true);
  $invoiceValue += $lineValue;
}
$this->SetFont('Arial', 'B', 10);
$this->setFillColor(170);
$this->Cell(100, 8, "Total: $invoiceValue");
$this->Cell(20, 8, $nf->formatCurrency($lineValue, $currency), 1, 1, 0, 'R', true);
`

View Helper:
`
class invoiceHelper extends PDFHelper
{
  // Header callback (called automatically by the FPDF Library)
  public function invoiceHeader()
  {
    $this->Image('logo.png', 10, 6, 30);
    $this->SetFont('Arial', 'B', 15);
    $this->Cell(0, 10, 'Best Widgets plc', 1, 0, 'C');
  }

  // Footer callback (called automatically by the FPDF Library)
  // $this->AliasNbPages() must have been called
  public function invoiceFooter()
  {
    $this->SetFont('Arial', 'I', 8);
    $this->SetXY(10, -15);
    $this->Cell(0, 6, $this->data['order']->number);
    $this->SetX();
    $this->Cell(0, 6, 'Page '.$this->PageNo().'/{nb}', 0, 0, 'C');
  }

  // Helper method to write an address
  public function writeAddress($address, $w, $h, $align = 'L')
  {
    static $attrs = array('extended_address', 'street_address', 'locality', 'region', 'postal_code', 'country_name');

    foreach ($attrs as $attr) {
      if (!empty($this->$address->$attr)) {
        $this->Cell($w, $h, $address->$attr, 0, 1, $align);
      }
    }
  }
}
`
