<?php
/**
 * Created by PhpStorm.
 * User: Edd
 * Date: 6/3/24
 * Time: 11:53 AM
 * Description: Here resides all variables that must be changed/updated for each client outreach portal
 */

interface IConfig {
    const HOST = "127.0.0.1";
\
    const SQL_PORT = 6578;

    const EmailPassword = "emailpassword";

    const SITE_URL = "http://localhost/outreach/";

    const Logo = "logo.png";

    const IsCoremedica = false;

    const TimeoutInterval = 600; // 3600 = 60 minutes, 600 = 10 minutes

    const LabName = "Demo LIS";

    /* Version
     * 1.0:     Started using the Version constant to control browser caching of js and css files
     * 1.1:     Bug fix when selecting a patient with a subscriber. In scripts.patients.js, the setDefaultSub function was using outdated logic.
     * 1.2:     Added Order Entry Settings. "Result Search Disabled" - Access to order entry section only
     * 1.3:     updated scripts.tests.js to fix some bugs with selecting common tests and to prevent duplicate tests from being selected
     * 1.4:
     *          - Added Support for ICD 10 codes
     *          - Added responsiveness on main admin page and search results page
     * 1.5:
     *          - Improved result search speed
     *          - Improved speed loading pending orders by limiting the query to select only non-receipted orders
     * 1.6      - Added Manifest report
     *          - Improved efficiency of the view pending orders query
     * 1.7      - Added Inconsistent Report
     * 1.8      - Improved processing time in order entry
     * 1.9      - Requires patient and subscriber arNo in order entry to be numeric
     * 1.91     - Fixed Invalidate function
     * 1.92     - Additional required fields in order entry - patient gender, patient address, diagnosis codes
     * 1.93     - New cumulative page design and downloadable cumulative jasper report
     * 1.94     - Can now have multiple users logged into under the same account at once
     * 1.95     - More info on the cumulative page
     * 1.96     - New datepicker calendar in order entry
     * 1.97     - Added Doctor E-Signatures
     * 2.0      - Nows uses pdf.js for displaying all reports
     *          - Now works on mobile browsers
     * 2.1      - date validation in order entry checks for two digit years
     * 2.2      - draggable table, limits to number of orders that can be displayed, and p on cumulative page
     * 2.3      - Improved print quality in pdf.js
     * 2.4      - new pdf.js viewer - fixed printing bug
     * 2.5      - Added user notifications
     * 2.61     - bug fix with order entry
     * 2.62     - allows alphanumeric patient and subscriber ids in order entry
     * 2.63     - UI bug fix with adding clients
     * 2.64     - Added location column when searching for clients/doctors in user maintenance
     * 2.65     - Eligibility lookup functionality
     * 2.66     - Added option of requiring eSignature on the doctor to submit a web order
     * 2.67     - Diagnosis validity checking in order entry
     * 2.69     - Excludes reference tests in order entry test searches
     * 2.70     - Button on search page to load print dialog for new results
     * 2.643    - Updated common tests and excluded tests to handle multi-location access in user maintenance and order entry
     * 2.8      - Added code to prevent multiple clicks on link in order entry
     * 2.82     - Fixed a bug with selecting the prescriptions and diagnosis codes on a patients previous order
     * 2.83     - Fixed issue with printing labels from the view requisition page
     * 2.84     - Updated Inconsistent report to get passed in clientId/doctorId to reduce report runtime
     * 2.85     - New Results button updated and Client specific required fields in order entry
     * 2.86     - Added patient/subscriber formatting and validation in order entry
     * 2.88     - Ability to Edit/Delete patient users. New password requirements
     * 2.93     - Multi users included in order entry patient search
     * 2.98     - Option for notifications to always display on the result search page
     * 2.99     - New functionality for attaching documents to web orders
     * 3.1      - Resizes web order documents to fit page better. Allows viewing of web order documents with result reports
     * 3.2      - Added support for patient prescriptions in order entry
     */
    const Version = 3.2;

    // specify which fields to use in the "Order Information" section of order entry
    const UseOldOrderInfoFormat = true; 

    const RoomNumberDisabled = false;
    const BedNumberDisabled = false;
    const PatientHeightDisabled = false;
    const PatientWeightDisabled = false;
    const PatientSmokerDisabled = false;
    const PocSectionDisabled = false;
	
    const RequireAdditionalOrderEntryFields = false; 

    const AllowReportTypeSelection = false;

    const PositiveTestsPageTitle = "Test Specific Results Report";

    const HasLabelPrint = false; 

    const OrderDateColHeaderText = "Date of Service"; 

    const SpecimenDateColHeader = "Collection Date"; 

    const PrintPatientIdSearch = true;

    const DefaultRestrictAll = false;

    const HasManifestReport = true; 

    const EditOrderTimeoutInterval = 15; // minutes

    const HasESignatureOnReq = true; 

    // Limit for the number of simultaneous logins under the same user, 0 = no limit
    const UserLoginLimit = 1;

    const CommonTestsFormat = 2;

    const DefaultESigAssignType = 1; 

    const PrintAllTestsOnReq = false; 

    const ExtraPasswordSecurity = false;

    const HasUserNotifications = false;

    const AlwaysShowNotifications = false;

    const HasBloodWellnessReport = false;

    const OrderEntryAutoPrint = true; 

    const HasMultiLocation = false;

    const HasCheckEligibility = false;

    const HasDiagnosisValidity = false;

    const HasNewResultsButton = false;

    const OrderEntryPatientEmail = false;

    const SalesPortalDisabled = true;
    const ReportsDisabled = false;
    const ViewAllDisabled = true;

    const ESignatureDrawDisabled = false;
    const ESignatureUploadDisabled = false;

    const HideOEInsurance = true;
    const HideOETests = false;
    const HideOEComments = true;
    const HideOEPrescriptions = false;

    const OrderEntryAdminDisabled = false;
    const OrderEntryAccessionSelectable = true;
    const OrderEntryDocumentsEnabled = true;
    const OrderEntryDocumentsFilePath = "C:/Apache24/htdocs/Outreach/usr/outreach/Documents/"; //"/usr/outreach/Documents/";
    const OEPOCBGRowColor = true;
}