<?php

namespace App\Mail;

use App\Models\Upload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\Service\RecordPdfService;
use App\Service\FormService;

class FormSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public $form, $record;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($recordId, $subject)
    {
        $this->record = RecordPdfService::getDecodedRecord($recordId);
        $this->form = RecordPdfService::getDecodedForm($this->record["form_id"]);
        $this->subject = $subject;

        $fs = new FormService($this->form, $this->record["fields"]);
        $form = $fs->getFormObject();
        $orderedElements = FormService::getElementsSortedByOrder($form, "order");
        $this->elements = $this->get_elements_plain($form["elements"], []);
    }
    public function get_elements_plain($elements, $all = []) {
        foreach($elements as $element) {
            $element = (object) $element;
            if($element->type === 'columns') {
                if(
                    isset($element->properties) &&
                    isset($element->properties->elements) &&
                    is_array($element->properties->elements) &&
                    count($element->properties->elements) > 0
                ) {
                    $all = $this->get_elements_plain($element->properties->elements, $all);
                }
            } else {
                $all[] = (array) $element;
            }
        }
        return $all;
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $pdfFileName = RecordPdfService::generateRecordPdfName(
            $this->record,
            $this->form,
        );
        $pdfString = RecordPdfService::generateRecordPdf(
            $this->record,
            $this->form,
            'A'
        );

        $formOptions = $this->form["options"];
        $isTableLayout = $formOptions["email-on-form-submition-table-template"] === "on";
        $files = Upload::where('record_id', $this->record["id"])->get();

        $has_file = count($files) > 0;
        // foreach($this->form["elements"] as $el) {
        //     if($el["type"] === "file" && !empty($this->record["fields"][$el['id']]) && !$has_file) {
        //         $has_file = true;
        //     }
        // }
        $elements = json_decode(json_encode($this->elements), true);
        $email = $this->view("emails.form-submitted")
            ->with([
                "isTableLayout" => $isTableLayout,
                "elements" => $elements,
                "values" => $this->record["fields"],
                "hasFile" => $has_file,
                "files" => $files,
                "recordId" => $this->record['id']
            ])
            ->subject($this->subject);

        if ($formOptions["email-on-form-submition-pdf-attachment"] === "on") {
            $email->attachData(
                $pdfString,
                $pdfFileName,
                ["mime" => "application/pdf"],
            );
        }
        return $email;
    }
}

