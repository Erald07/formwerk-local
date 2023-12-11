<?php

namespace App\Helpers;

use setasign\Fpdi\Fpdi;

class PdfTemplate
{
    public static function mergePDF ($formSetting, $filename, $pdf) {
        // merge pdfs
        $template_1 = isset($formSetting['form-background-first-page-file']) ? $formSetting['form-background-first-page-file'] : $formSetting['form-background-other-page-file'];
        $template_2 =isset($formSetting['form-background-other-page-file']) ? $formSetting['form-background-other-page-file'] : $formSetting['form-background-first-page-file'];
        $template1Header = isset($formSetting['form-background-first-page-top']) ? $formSetting['form-background-first-page-top'] : 0;
        $template1Left = isset($formSetting['form-background-first-page-left']) ? $formSetting['form-background-first-page-left'] : 0;
        $template1Right = isset($formSetting['form-background-first-page-right']) ? $formSetting['form-background-first-page-right'] : 0;
        $template1Footer = isset($formSetting['form-background-first-page-bottom']) ? $formSetting['form-background-first-page-bottom'] : 0;
        $template2Header = isset($formSetting['form-background-other-page-top']) ? $formSetting['form-background-other-page-top'] : 0;
        $template2Left = isset($formSetting['form-background-other-page-left']) ? $formSetting['form-background-other-page-left'] : 0;
        $template2Right = isset($formSetting['form-background-other-page-right']) ? $formSetting['form-background-other-page-right'] : 0;
        $template2Footer = isset($formSetting['form-background-other-page-bottom']) ? $formSetting['form-background-other-page-bottom'] : 0;
        $header_1 = isset($formSetting['form-background-first-page-file']) ? $template1Header : $template2Header;
        $left_1 = isset($formSetting['form-background-first-page-file']) ? $template1Left : $template2Left;
        $right_1 = isset($formSetting['form-background-first-page-file']) ? $template1Right : $template2Right;
        $footer_1 = isset($formSetting['form-background-first-page-file']) ? $template1Footer : $template2Footer;
        $header_2 = isset($formSetting['form-background-other-page-file']) ? $template2Header : $template1Header;
        $left_2 = isset($formSetting['form-background-other-page-file']) ? $template2Left : $template1Left;
        $right_2 = isset($formSetting['form-background-other-page-file']) ? $template2Right : $template1Right;
        $footer_2 = isset($formSetting['form-background-other-page-file']) ? $template2Footer : $template1Footer;

        $pdf->save(public_path() .'/'.$filename . '.pdf');
        // initiate FPDI
        $fpdi = new Fpdi('P');
        // add a page
        $fpdi->AddPage();
        $count = $fpdi->setSourceFile(public_path() .'/'.$filename . '.pdf');
        unset($fpdi);
        $fpdi = new Fpdi('P');
        for ($i = 1; $i <= $count; $i++) {
            if ($i === 1) {
                $template_file_name = public_path(str_replace('public', 'storage', $template_1));
            } else {
                $template_file_name = public_path(str_replace('public', 'storage', $template_2));
            }
            // set the source file to doc2.pdf and import a page
            $fpdi->setSourceFile($template_file_name);
            $tplIdx = $fpdi->importPage(1);
            $size = $fpdi->getTemplateSize($tplIdx);
            $px = 0;
            $py = 0;
            $pwidth = $size['width'];
            $pheight = $size['height'];
            if ($i === 1) {
                $x = $px + $left_1;
                $y = $py + $header_1;
                $width = $pwidth - $x - $right_1;
                $height = $pheight - $y - $footer_1;
            } else {
                $x = $px + $left_2;
                $y = $py + $header_2;
                $width = $pwidth - $x - $right_2;
                $height = $pheight - $y - $footer_2;
            }
            $fpdi->AddPage($size['orientation'], array($size['width'], $size['height']));
            // use the imported page and place it at point 100,10 with a width of 210 mm
            $fpdi->useTemplate($tplIdx, 0, 0, $size['width'], $size['height'], false);
            // set the source file to doc1.pdf and import a page
            $fpdi->setSourceFile(public_path() .'/'.$filename . '.pdf');
            $tplIdx = $fpdi->importPage($i);
            // use the imported page and place it at point 10,10 with a width of 210 mm
            $fpdi->useTemplate($tplIdx, $x, $y, $width, $height, false);
        }
        unlink(public_path() .'/'.$filename . '.pdf');
        return $fpdi->Output($filename . '.pdf', 'D');
    }
}