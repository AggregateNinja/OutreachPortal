<?php
/*
 * https://stackoverflow.com/a/2780518
 */
require_once 'BaseObject.php';
require_once 'Report.php';
require_once 'IJasperServer.php';

abstract class ReportCreator implements IJasperServer {

    protected $Report;

    protected abstract function factoryMethod(array $reportData);
    protected abstract function addLogEntry(array $logData = null);

    public function startFactory($data, array $settings = null) {

        $this->Report = $this->factoryMethod($data, $settings);

        $excludeHeaders = false;
        $printReport = false;
        $base64Encode = false;
        $returnPdf = false;
        if ($settings != null) {
            if (array_key_exists("ExcludeHeaders", $settings) && $settings['ExcludeHeaders'] == true) {
                $excludeHeaders = true;
            }
            if (array_key_exists("PrintReport", $settings) && $settings['PrintReport'] == true) {
                $printReport = true;
            }
            if (array_key_exists("Base64Encode", $settings) && $settings['Base64Encode'] == true) {
                $base64Encode = true;
            }
            if (array_key_exists("ReturnPdf", $settings) && $settings['ReturnPdf'] == true) {
                $returnPdf = true;
            }
        }

        if ($printReport) {
            $report = $this->Report;
            $numLabels = $data['numLabels'];
            $canvasHtml = "";
            $js = "
            function isIE() {
                var ua = window.navigator.userAgent;
                var msie = ua.indexOf('MSIE ');

                if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./))
                    return true;
                else
                    return false;
            }

            var scale = 10;
            //if (isIE() == true) {
            //    var scale = 2.7;
            //}

            var __reportName = '" . $report . "'; ";
            for ($i = 0; $i < $numLabels; $i++) {

                $canvasId = "the-canvas-$i";

                $canvasHtml .= "<canvas id=\"the-canvas-" . $i . "\"></canvas>";
                $js .= "
                    PDFJS.getDocument('/outreach/orderentry/" . $report . "').then(function(pdf) {
                        // Using promise to fetch the page
                        pdf.getPage(1).then(function(page) {
                            var viewport = page.getViewport(scale);

                            // Prepare canvas using PDF page dimensions
                            var canvas = document.getElementById('$canvasId');
                            var context = canvas.getContext('2d');

                            canvas.height = viewport.height;
                            canvas.width = viewport.width;

                            // Render PDF page into canvas context
                            var renderContext = {
                                canvasContext: context,
                                viewport: viewport
                            };";
                if ($i == $numLabels - 1) {
                    $js .= "page.render(renderContext).promise.then(function(){

                                setTimeout(function (){

                                    $.ajax({
                                            type: 'POST',
                                            url: 'indexb.php',
                                            data: { action: 2, reportName: __reportName },
                                            cache: false,
                                            dataType: 'text',
                                            success: function() {
                                                //window.document.close();
                                                window.focus();
                                                window.print();
                                                //window.setTimeout(function() {newWin.close()}, 500);
                                            }
                                        });
                                }, 500); // How long do you want the delay to be (in milliseconds)?
                            });";
                    //$js .= "page.render(renderContext);";
                } else {
                    $js .= "page.render(renderContext);";
                }

                $js .= "
                        });
                    });
                ";
            }

            $canvasStyle = "canvas {
                            display: block;
                            width: 432px;
                            height: 162px;
                        }";
            if (self::PrintVerticalLabel == true) {
                $canvasStyle = "canvas {
                            display: block;
                            width: 100px;
                            height: 267px;
                            padding-top: 20px;
                        }";
            }

            echo "
                <!doctype html>
                <html>
                    <head>
                        <style type=\"text/css\">
                        html, body {
                            margin: 0;
                            padding: 0;
                            height: 100%;
                        }
                        $canvasStyle
                        </style>
                        <style type=\"text/css\" media=\"print\">
                        @page {
                            size: auto;
                            margin: 0;
                        }
                        </style>
                        <script src=\"//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js\"></script>
                        <!--
                        <script type=\"text/javascript\" src=\"/outreach/pdf/src/shared/util.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/src/display/api.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/src/display/metadata.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/src/display/canvas.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/src/display/webgl.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/src/display/pattern_helper.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/src/display/font_loader.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/src/display/annotation_helper.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/ui_utils.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/default_preferences.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/preferences.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/download_manager.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/view_history.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/pdf_rendering_queue.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/pdf_page_view.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/text_layer_builder.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/annotations_layer_builder.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/pdf_viewer.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/thumbnail_view.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/document_outline_view.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/document_attachments_view.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/pdf_find_bar.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/pdf_find_controller.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/pdf_history.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/secondary_toolbar.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/presentation_mode.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/grab_to_pan.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/hand_tool.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/overlay_manager.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/password_prompt.js\"></script>
                        <script type=\"text/javascript\" src=\"/outreach/pdf/web/document_properties.js\"></script>
                        -->
                        <script type=\"text/javascript\" src=\"/outreach/pdf/build/pdf.js\"></script>
                        <script type=\"text/javascript\">
                            //PDFJS.workerSrc = '/outreach/pdf/src/worker_loader.js';
                            PDFJS.workerSrc = '/outreach/pdf/build/pdf.worker.js';
                            'use strict';

                            $(document).ready(function() {
                                $js
                            });

                             //http://stackoverflow.com/a/18325463
                            (function() {
                                var beforePrint = function() { };
                                var afterPrint = function() {
                                    location.replace('/outreach/orderentry/index.php');
                                };
                                if (window.matchMedia) {
                                    var mediaQueryList = window.matchMedia('print');
                                    mediaQueryList.addListener(function(mql) {
                                        if (mql.matches) {
                                            beforePrint();
                                        } else {
                                            afterPrint();
                                        }
                                    });

                                }

                                window.onbeforeprint = beforePrint;
                                window.onafterprint = afterPrint;

                            }());
                        </script>
                    </head>
                    <body>
                        $canvasHtml
                    </body>
                </html>
            ";
        } else if ($excludeHeaders && $base64Encode) {
            //echo base64_encode($this->Report->EncodedPdf);
            if ($returnPdf) {
                return base64_encode($this->Report->EncodedPdf);
            } else {
                echo base64_encode($this->Report->EncodedPdf);
            }
        } else if ($excludeHeaders) {
            echo $this->Report;
        } else if (!is_bool($this->Report->EncodedPdf)) {
            $this->doHtmlHeaders($settings);
            echo $this->Report->EncodedPdf;
        } else {
            echo "
                <p style='text-align: center; margin: 0 auto;'>
                    There was an error generating this report.
                </p>";
        }
    }

    protected function doHtmlHeaders(array $settings = null) {
        $name = $this->Report->name;
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Description: File Transfer');
        if (!empty($settings) && array_key_exists("DownloadReport", $settings) && $settings['DownloadReport'] == true) {
            header('Content-Disposition: attachment; filename=' . $name . date("Ymdhis") . '.pdf');
        } else {
            header('Content-Disposition: inline; filename=' . $name . date("Ymdhis") . '.pdf');
        }
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . strlen($this->Report->EncodedPdf));
        header('Content-Type: application/pdf');
    }

    // reference: http://stackoverflow.com/a/17212266
    protected function saveReport($report, $name) {
        ob_start();
        echo $report;
        $output_so_far = ob_get_contents();
        ob_clean();
        $fileName = $name . "_" . date("Ymdhis") . ".pdf";
        file_put_contents($fileName , $output_so_far);
        return $fileName;
    }

    protected function getIpAddress() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];

        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { // It is a proxy address
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];

        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        if (isset($ip) && $ip != null && !empty($ip)) {
            return ip2long($ip);
        }
        return null;
    }
}