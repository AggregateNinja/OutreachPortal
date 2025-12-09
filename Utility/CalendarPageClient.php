<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 9/2/15
 * Time: 12:20 PM
 */

require_once 'LabstaffPageClient.php';

class CalendarPageClient extends LabstaffPageClient {

    public function __construct(array $data = null) {
        parent::__construct($data);

        $this->addStylesheet("/fullcalendar/fullcalendar.css");
        $this->addStylesheet("/fullcalendar/fullcalendar.print.css", array("media" => "print"));
        $this->addStylesheet("css/calendar.css");

        $this->addScript("/fullcalendar/lib/moment.min.js", false);
        $this->addScript("/fullcalendar/lib/jquery-ui.custom.min.js", false);
        $this->addScript("/fullcalendar/fullcalendar.min.js", false);
        $this->addScript("js/calendar.js", false);
    }

    public function printPage(array $settings = null) {
        $html = "
            <main>
                <div class=\"container\" style=\"padding-top: 10px;\">
                    <div class=\"row\">
                        <div class=\"col s3 m3 l3\">
                            <div id=\"external-events\">
                                <h4>Draggable Events</h4>
                                <div class=\"fc-event\">My Event 1</div>
                                <div class=\"fc-event\">My Event 2</div>
                                <div class=\"fc-event\">My Event 3</div>
                                <div class=\"fc-event\">My Event 4</div>
                                <div class=\"fc-event\">My Event 5</div>
                                <p>
                                    <input type=\"checkbox\" id=\"drop-remove\" />
                                    <label for=\"drop-remove\">remove after drop</label>
                                </p>
                            </div>
                        </div>
                        <div class=\"col s9 m9 l9\">
                            <div id=\"calendar\"></div>
                        </div>
                    </div>



                </div>
            </main>
        ";

        echo $html;
    }
}