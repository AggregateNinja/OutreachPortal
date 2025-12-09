<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 4/8/2020
 * Time: 10:16 AM
 */
interface IPatientConfig {
    const LabName = "Demo LIS";

    const Logo = "logo.png";

    const EmailHost = "email-smtp.us-east-1.amazonaws.com";
    const ConfigSet = "ConfigSet";
    const UseConfigSet = true;
    const EmailUsername = "AKIA2VOQQYZFTQHRLVCI"; 
    const EmailPort = 587;

    const LogoWidth = "262"; 

    const LogoHeight = "115"; 

    const HTTP = "https";
	
    const URL = "demolis.com";

    const Email = "noreply@demolis.com";

    const EmailPassword = "BJtXu+4dfwVmHOR66vDw2jJzfiMkZnWF7I5afiiZaf1s"; 

    const States = array(
        'AL'=>'Alabama',
        'AK'=>'Alaska',
        //'AS'=>'American Samoa',
        'AZ'=>'Arizona',
        'AR'=>'Arkansas',
        'CA'=>'California',
        'CO'=>'Colorado',
        'CT'=>'Connecticut',
        'DE'=>'Delaware',
        'DC'=>'District of Columbia',
        //'FM'=>'Federated States of Micronesia',
        'FL'=>'Florida',
        'GA'=>'Georgia',
        //'GU'=>'Guam GU',
        'HI'=>'Hawaii',
        'ID'=>'Idaho',
        'IL'=>'Illinois',
        'IN'=>'Indiana',
        'IA'=>'Iowa',
        'KS'=>'Kansas',
        'KY'=>'Kentucky',
        'LA'=>'Louisiana',
        'ME'=>'Maine',
        //'MH'=>'Marshall Islands',
        'MD'=>'Maryland',
        'MA'=>'Massachusetts',
        'MI'=>'Michigan',
        'MN'=>'Minnesota',
        'MS'=>'Mississippi',
        'MO'=>'Missouri',
        'MT'=>'Montana',
        'NE'=>'Nebraska',
        'NV'=>'Nevada',
        'NH'=>'New Hampshire',
        'NJ'=>'New Jersey',
        'NM'=>'New Mexico',
        'NY'=>'New York',
        'NC'=>'North Carolina',
        'ND'=>'North Dakota',
        //'MP'=>'Northern Marina Islands',
        'OH'=>'Ohio',
        'OK'=>'Oklahoma',
        'OR'=>'Oregon',
        //'PW'=>'Palau',
        'PA'=>'Pennsylvania',
        'PR'=>'Puerto Rico',
        'RI'=>'Rhode Island',
        'SC'=>'South Carolina',
        'SD'=>'South Dakota',
        'TN'=>'Tennessee',
        'TX'=>'Texas',
        'UT'=>'Utah',
        'VT'=>'Vermont',
        //'VI'=>'Virgin Islands',
        'VA'=>'Virginia',
        'WA'=>'Washington',
        'WV'=>'West Virginia',
        'WI'=>'Wisconsin',
        'WY'=>'Wyoming'
    );

    const CovidTestNumber = 213; 

    const RegisterPageTitle = "Patient Results Portal"; 

    const LoginPageTitle = "Patient Portal";

    const HasWebOrder = true;

    const HasCards = false;

    const Directory = ""; // leave empty for production, development = /patients
    
    const FilePath = 'C:\Apache24\htdocs\Outreach\var\www\patients\cards\\';

    const ClientNo = 1;
    const IdClients = 1;
}