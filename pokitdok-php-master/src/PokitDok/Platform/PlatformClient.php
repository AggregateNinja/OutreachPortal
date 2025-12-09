<?php
/**
 * Copyright (C) 2014, All Rights Reserved, PokitDok, Inc.
 * http://www.pokitdok.com
 *
 * Please see the LICENSE.txt file for more information.
 * All other rights reserved.
 *
 *	PokitDok Platform Client for PHP
 *		Consume the REST based PokitDok platform API
 *		https://platform.pokitdok.com/
 */


namespace PokitDok\Platform;

use PokitDok\Common\Oauth2ApplicationClient;

/**
 * Class PlatformClient The PokitDok  API allows you to perform X12 transactions,
 *  find healthcare providers and get information on health care procedure pricing.
 * See docs here: https://platform.pokitdok.com/
 *
 * Common Query parameters:
 * async	Whether the API call is asynchronous. For Resources that offer both synchronous and asynchronous operation,
 *          a boolean can be used for this parameter to specify which mode of operation you desire; if the async
 *          parameter is omitted, the synchronous mode will be used. For POST requests, the async parameter should be
 *          included along with other JSON data being POSTed. When async is true, the API client has the option of
 *          including a callback URL so that it can be notified when the asynchronous processing is complete.
 * dir	    The direction that a list is sorted, ascending or descending (only for collection requests)
 * limit	The number of Resources to return in a list (only for collection requests)
 * offset	The number of Resources to skip when paging through a list (only for collection requests)
 * sort	    The field to sort the list of Resources by (only for collection requests)
 *
 * @package PokitDok\Platform
 */
class PlatformClient extends Oauth2ApplicationClient
{
    const POKITDOK_PLATFORM_API_SITE = 'https://platform.pokitdok.com';

    const POKITDOK_PLATFORM_API_TOKEN_URL = '/oauth2/token';
    const POKITDOK_PLATFORM_API_TOKEN_REFRESH_URL = '/oauth2/refresh';
    const POKITDOK_PLATFORM_API_VERSION_PATH = '/api/v4';

    const POKITDOK_PLATFORM_API_ENDPOINT_ELIGIBILITY = '/eligibility/';
    const POKITDOK_PLATFORM_API_ENDPOINT_PROVIDERS = '/providers/';
    const POKITDOK_PLATFORM_API_ENDPOINT_CLAIMS = '/claims/';
    const POKITDOK_PLATFORM_API_ENDPOINT_CLAIMS_STATUS = '/claims/status';
    const POKITDOK_PLATFORM_API_ENDPOINT_ENROLLMENT = '/enrollment/';
    const POKITDOK_PLATFORM_API_ENDPOINT_DEDUCTIBLE = '/deductible/';
    const POKITDOK_PLATFORM_API_ENDPOINT_PRICE_INSURANCE = '/prices/insurance';
    const POKITDOK_PLATFORM_API_ENDPOINT_PRICE_CASH = '/prices/cash';
    const POKITDOK_PLATFORM_API_ENDPOINT_ACTIVITIES = '/activities/';
    const POKITDOK_PLATFORM_API_ENDPOINT_TRADING_PARTNERS = '/tradingpartners/';
    const POKITDOK_PLATFORM_API_ENDPOINT_PLANS = '/plans/';
    const POKITDOK_PLATFORM_API_ENDPOINT_REFERRALS = '/referrals/';
    const POKITDOK_PLATFORM_API_ENDPOINT_AUTHORIZATIONS = '/authorizations/';
    const POKITDOK_PLATFORM_API_ENDPOINT_SCHEDULERS = '/schedule/schedulers/';
    const POKITDOK_PLATFORM_API_ENDPOINT_APPOINTMENT_TYPES = '/schedule/appointmenttypes/';
    const POKITDOK_PLATFORM_API_ENDPOINT_SLOTS = '/schedule/slots/';
    const POKITDOK_PLATFORM_API_ENDPOINT_APPOINTMENTS = '/schedule/appointments/';
    const POKITDOK_PLATFORM_API_ENDPOINT_MPC = '/mpc/';
    const POKITDOK_PLATFORM_API_ENDPOINT_ICD_CONVERT = '/icd/convert/';
    const POKITDOK_PLATFORM_API_ENDPOINT_CCD = '/ccd/';
    const POKITDOK_PLATFORM_API_ENDPOINT_CLAIMS_CONVERT = '/claims/convert';
    const POKITDOK_PLATFORM_API_ENDPOINT_ENROLLMENT_SNAPSHOT = '/enrollment/snapshot';
    const POKITDOK_PLATFORM_API_ENDPOINT_IDENTITY = '/identity/';
    const POKITDOK_PLATFORM_API_ENDPOINT_PHARMACY_PLANS = '/pharmacy/plans';
    const POKITDOK_PLATFORM_API_ENDPOINT_PHARMACY_FORMULARY = '/pharmacy/formulary';


    private $_usage = null;
    private $_version_path = self::POKITDOK_PLATFORM_API_VERSION_PATH;

    public function applyResponse()
    {
        $json_response = $this->getResponseBody();
        if (!($json_response instanceof \stdClass)) {
            throw new \Exception($this->getResponseBody(), $this->getStatus());
        }
        if (isset($json_response->errors)) {
            throw new \Exception(json_encode($json_response->errors), $this->getStatus());
        }
        $this->_usage = $json_response->meta;

        return $this->getResponse();
    }

    /**
     * @param string $id PokitDok API Client ID
     * @param string $secret PokitDok API Client Secret
     * @param int $request_timeout Timeout for all requests (default is 90(s))
     * @param string $access_token_json JSON string of access token response for saved authentication token
     * @param string $cert_file Full path to certificate file containing CA certs
     * @param string $redirect_uri  URL to redirect to for the Platform Application, see Application settings
     * @param callable $token_refresh_callback Callback function invoked when the token is refreshed
     * @param array $scope array of strings representing the requested scopes
     * @param string $code The authorization code received by the scope grant of the Platform Application
     */
    public function __construct(
        $id,
        $secret,
        $request_timeout = self::DEFAULT_TIMEOUT,
        $access_token_json = null,
        $cert_file = '',
        $redirect_uri = null,
        callable $token_refresh_callback = null,
        $scope = null,
        $code = null)
    {
        parent::__construct(
            $id,
            $secret,
            $request_timeout,
            $access_token_json,
            $cert_file,
            $redirect_uri,
            $token_refresh_callback,
            $scope,
            $code
        );

        $this->setApiBaseUrl(self::POKITDOK_PLATFORM_API_SITE . $this->_version_path);
        $this->setApiTokenUrl(self::POKITDOK_PLATFORM_API_SITE . self::POKITDOK_PLATFORM_API_TOKEN_URL);
    }

    /**
     * @param string $version_path
     */
    public function setVersionPath($version_path)
    {
        $this->_version_path = $version_path;
        $this->setApiBaseUrl(self::POKITDOK_PLATFORM_API_SITE . $this->_version_path);
    }

    /**
     * @return string
     */
    public function getVersionPath()
    {
        return $this->_version_path;
    }

    /**
     * Usage statistics for most recent request
     *
     * @return \stdClass Object
     * 	    rate_limit_cap, {int} The amount of requests available per hour
     * 	    rate_limit_reset, {int} The time (Unix Timestamp) when the rate limit amount resets
     *		rate_limit_amount, {int} The amount of requests made during the current rate limit period
     *		credits_billed, {int} The amount of credits billed for this request
     *		credits_remaining, {int} The amount of credits remaining on your API account
     *		processing_time, {int} The time to process the request in milliseconds
     *		next, {string}, A url pointing to the next page of results
     *		previous, {string} A url pointing to the previous page of results
     * @throws \Exception On data or API errors
     */
    public function usage()
    {
        if (!isset($this->_usage)) {
            $this->eligibility(
                array(
                    'member' => array(
                        'id' => "W000000000",
                        'birth_date' => "1970-01-01",
                        'first_name' => "Jane",
                        'last_name' => "Doe"
                    ),
                    'provider' => array(
                        'npi' => "1467560003",
                        'last_name' => "AYA-AY",
                        'first_name' => "JEROME"
		        ),
                'service_types' => array("health_benefit_plan_coverage"),
                'trading_partner_id' => 'MOCKPAYER'
            ));
        }

        return $this->_usage;
    }

    /**
     * Retrieve the data for a specified provider id or query parameters
     * See docs here: https://platform.pokitdok.com/
     *
     * @param mixed $providers_request String of the PokitDok UUID for the provider OR Array of query parameters
     *  Query parameters:
     * 	    organization_name, The business practice name
     *		first_name, Provider first name
     *	    last_name, Provider first name
     *	    specialty, Provider specialty name from NUCC/NPI taxonomy
     *	    city, Provider city
     *	    state, Provider state
     *	    zipcode, Provider 5-digit zip code
     *	    radius, Search distance from geographic centerpoint, with unit (e.g. “1mi” or “50mi”)
     *		    (Only used when city, state, or zipcode is passed)
     * @return \PokitDok\Common\HttpResponse Response object with providers data,
     *      see API documentation on https://platform.pokitdok.com/
     * @throws \Exception On data or API errors
     */
    public function providers($providers_request = '')
    {
        $this->request(
            'GET',
            self::POKITDOK_PLATFORM_API_ENDPOINT_PROVIDERS,
            $providers_request,
            "application/json"
        );

        return $this->applyResponse();
    }

    /**
     * Determine eligibility via an EDI 270 Request For Eligibility.
     * See docs here: https://platform.pokitdok.com/
     *
     * @param array $eligibility_request Array representing an EDI 270 Request For Eligibility as JSON
     * @return \PokitDok\Common\HttpResponse Response object with eligibility data
     * @throws \Exception On HTTP errors (status > 299)
     */
    public function eligibility(array $eligibility_request)
    {
        $this->request(
            'POST',
            self::POKITDOK_PLATFORM_API_ENDPOINT_ELIGIBILITY,
            $eligibility_request,
            "application/json"
        );

        if (empty($eligibility_request)) {
            $json_response = $this->getResponseBody();
            if (!($json_response instanceof \stdClass)) {
                throw new \Exception($this->getResponseBody(), $this->getStatus());
            }
            $this->_usage = $json_response->meta;
            return $this->getResponse();
        }

        return $this->applyResponse();
    }

    /**
     * Create a new claim, via the filing of an EDI 837 Professional Claims, to the designated Payer.
     * See docs here: https://platform.pokitdok.com/
     *
     * @param array $claims_request Array representing EDI 837 Professional Claim as JSON
     * @return \PokitDok\Common\HttpResponse Response object with claims data
     * @throws \Exception On HTTP errors (status > 299)
     */
    public function claims(array $claims_request)
    {
        $this->request(
            'POST',
            self::POKITDOK_PLATFORM_API_ENDPOINT_CLAIMS,
            $claims_request,
            "application/json"
        );

        return $this->applyResponse();
    }

    /**
     * Ascertain the status of the specified claim, via the filing of an EDI 276 Claims Status.
     * See docs here: https://platform.pokitdok.com/
     *
     * @param array $claims_status Array representing EDI 276 Claims Status as JSON
     * @return \PokitDok\Common\HttpResponse Response object with claimsStatus data,
     *      see API documentation on https://platform.pokitdok.com/
     * @throws \Exception On HTTP errors (status > 299)
     */
    public function claims_status(array $claims_status)
    {
        $this->request(
            'POST',
            self::POKITDOK_PLATFORM_API_ENDPOINT_CLAIMS_STATUS,
            $claims_status,
            "application/json"
        );

        return $this->applyResponse();
    }

    /**
     * Return a list of insurance prices for a given procedure (by CPT Code) in a given region (by ZIP Code).
     * See docs here: https://platform.pokitdok.com/
     *
     * @param array $price_insurance_request Array of price query parameters
     *  Query parameters:
     * 	    cpt_code, {string} The CPT code of the procedure in question.
     *		zip_code, {string} Postal code in which to search for procedures
     * @return \PokitDok\Common\HttpResponse Response object with priceInsurance data,
     * @throws \Exception On HTTP errors (status > 299)
     */
    public function insurance_prices(array $price_insurance_request)
    {
        $this->request(
            'GET',
            self::POKITDOK_PLATFORM_API_ENDPOINT_PRICE_INSURANCE,
            $price_insurance_request,
            "application/json"
        );

        return $this->applyResponse();
    }

    /**
     * Return a list of cash prices for a given procedure (by CPT Code) in a given region (by ZIP Code).
     * See docs here: https://platform.pokitdok.com/
     *
     * @param array $price_cash_request Array of price query parameters
     *  Query parameters:
     * 	    cpt_code, {string} The CPT code of the procedure in question.
     *		zip_code, {string} Postal code in which to search for procedures
     * @return \PokitDok\Common\HttpResponse Response object with priceCash data,
     * @throws \Exception On HTTP errors (status > 299)
     */
    public function cash_prices(array $price_cash_request)
    {
        $this->request(
            'GET',
            self::POKITDOK_PLATFORM_API_ENDPOINT_PRICE_CASH,
            $price_cash_request,
            "application/json"
        );

        return $this->applyResponse();
    }

    /**
     * Call the activities endpoint to get a listing of current activities,
     * a query string parameter ‘parent_id’ may also be used with this API to get information about
     * sub-activities that were initiated from a batch file upload.
     * See docs here: https://platform.pokitdok.com/
     *
     * @param mixed $activities_request String of the PokitDok ID for the activity OR Array of query parameters
     *  Query parameters:
     *  _id, {string} ID of this Activity
     *  name, {string} Activity name
     *  callback_url, {string} URL that will be invoked to notify the client application that this Activity has completed.
     *  	We recommend that you always use https for callback URLs used by your application.
     *  history, {list} Historical status of the progress of this Activity
     *  state, {dict} Current state of this Activity
     *  transition_path, {list} The list of state transitions that will be used for this Activity.
     *  remaining_transitions, {list} The list of remaining state transitions that the activity has yet to go through.
     *  parameters, {dict} The parameters that were originally supplied to the activity
     *  units_of_work, {int} The number of ‘units of work’ that the activity is operating on.
     *  	This will typically be 1 for real-time requests like /eligibility.  If an activity
     *  	does show a value greater than 1 for units_of_work, the client application can fetch detailed information
     *  	about each one of the activities processing those units of work by using the
     *  	/activities/?parent_id=&lt;activity_id&gt; API
     * @return \PokitDok\Common\HttpResponse Response object with activities data
     * @throws \Exception On HTTP errors (status > 299)
     */
    public function activities($activities_request = '')
    {
        $this->request(
            'GET',
            self::POKITDOK_PLATFORM_API_ENDPOINT_ACTIVITIES,
            $activities_request
        );

        return $this->applyResponse();
    }

    /**
      * Retrieve a list of trading partners or submit an id to get info for a
      * specific trading partner.
      * See docs here: https://platform.pokitdok.com/
      *
      * @param string $trading_partner_id id of the requested trading partner
      *                                   leave blank for full listing
      * @return \PokitDok\Common\HttpResponse Response object with trading partner data
      * @throws \Exception On HTTP errors (status > 299)
      */
    public function trading_partners($trading_partner_id = '')
     {
       $this->request(
           'GET',
           self::POKITDOK_PLATFORM_API_ENDPOINT_TRADING_PARTNERS,
           $trading_partner_id,
           "application/json"
       );

       return $this->applyResponse();
     }

    /**
      * Retrieve data on plans based on the parameters given.
      * See docs here: https://platform.pokitdok.com/
      *
      * @param array $plans_request Array of query parameters.
      * @return \PokitDok\Common\HttpResponse Response object with plans data,
      * @throws \Exception On HTTP errors (status > 299)
      */
    public function plans(array $plans_request = array())
    {
    $this->request(
        'GET',
        self::POKITDOK_PLATFORM_API_ENDPOINT_PLANS,
        $plans_request,
        "application/json"
    );

    return $this->applyResponse();
    }

    /**
     * Request approval for a referral to another health care provider.
     * See docs here: https://platform.pokitdok.com/
     *
     * @param array $referral_request Array representing a referral request
     * @return \PokitDok\Common\HttpResponse Response object with referral data,
     * @throws \Exception On HTTP errors (status > 299)
     */
    public function referrals(array $referral_request)
    {
        $this->request(
            'POST',
            self::POKITDOK_PLATFORM_API_ENDPOINT_REFERRALS,
            $referral_request,
            "application/json"
        );

        return $this->applyResponse();
    }

    /**
     * Submit an authorization request.
     * See docs here: https://platform.pokitdok.com/
     *
     * @param array $authorization_request Array representing an authorization request
     * @return \PokitDok\Common\HttpResponse Response object with authorization data,
     * @throws \Exception On HTTP errors (status > 299)
     */
    public function authorizations(array $authorization_request)
    {
        $this->request(
            'POST',
            self::POKITDOK_PLATFORM_API_ENDPOINT_AUTHORIZATIONS,
            $authorization_request,
            "application/json"
        );

        return $this->applyResponse();
    }

    /**
     * Get a list of (or specific by UUID) the supported Scheduling Systems and their details.
     * See docs here: https://platform.pokitdok.com/
     *
     * @param string $schedulers_request string The UUID of the Scheduler (scheduler_uuid), empty gets the entire list
     * @return \PokitDok\Common\HttpResponse Response object with scheduler details,
     *      see API documentation on https://platform.pokitdok.com/
     * @throws \Exception On HTTP errors (status > 299)
     */
    public function schedulers($schedulers_request = '')
    {
        $this->request(
            'GET',
            self::POKITDOK_PLATFORM_API_ENDPOINT_SCHEDULERS,
            $schedulers_request
        );

        return $this->applyResponse();
    }

    /**
     * Get a list of (or specific by UUID) the supported Appointment Types and their details.
     * See docs here: https://platform.pokitdok.com/
     *
     * @param string $appointment_types_request The UUID of the Appointment Type (appointment_type_uuid), empty gets
     *          the entire list
     * @return \PokitDok\Common\HttpResponse Response object with appointment type details
     * @throws \Exception On HTTP errors (status > 299)
     */
    public function appointment_types($appointment_types_request = '')
    {
        $this->request(
            'GET',
            self::POKITDOK_PLATFORM_API_ENDPOINT_APPOINTMENT_TYPES,
            $appointment_types_request
        );

        return $this->applyResponse();
    }

    /**
     * Submit an open slot for a provider's schedule
     * See docs here: https://platform.pokitdok.com/
     *
     * @param array $create_slot_request Array of open slot details
     * @return \PokitDok\Common\HttpResponse Response object of the booked Appointment
     * @throws \Exception On HTTP errors (status > 299)
     */
    public function schedule_slots(array $create_slot_request)
    {
        $this->request(
            'POST',
            self::POKITDOK_PLATFORM_API_ENDPOINT_SLOTS,
            $create_slot_request,
            "application/json"
        );

        return $this->applyResponse();
    }

    /**
     * Query for open appointment slots (using pd_provider_uuid and location) or booked appointments
     *      (using patient_uuid) given query parameters, or get a single appointment given a UUID (pd_appointment_uuid)
     * See docs here: https://platform.pokitdok.com/
     *
     * @param array|string $appointments_request Array of query parameters or string of the UUID (pd_appointment_uuid)
     * @return \PokitDok\Common\HttpResponse Response object with Open Slot or Appointment details
     * @throws \Exception On HTTP errors (status > 299)
     */
    public function appointments($appointments_request = '')
    {
        $this->request(
            'GET',
            self::POKITDOK_PLATFORM_API_ENDPOINT_APPOINTMENTS,
            $appointments_request
        );

        return $this->applyResponse();
    }

    /**
     * Book appointment for an open slot. Put data contains patient attributes and description.
     * See docs here: https://platform.pokitdok.com/
     *
     * @param string $book_appointment_uuid The Appointment UUID (pd_appointment_uuid) to book
     * @param array $book_appointment_request Array of the patient attributes and description
     * @return \PokitDok\Common\HttpResponse Response object of the booked Appointment
     * @throws \Exception On HTTP errors (status > 299)
     */
    public function book_appointment($book_appointment_uuid, array $book_appointment_request)
    {
        $this->request(
            'PUT',
            self::POKITDOK_PLATFORM_API_ENDPOINT_APPOINTMENTS. $book_appointment_uuid,
            $book_appointment_request,
            "application/json"
        );

        return $this->applyResponse();
    }

    /**
     * Update appointment description. Put data contains description.
     * See docs here: https://platform.pokitdok.com/
     *
     * @param string $update_appointment_uuid The Appointment UUID (pd_appointment_uuid) to update
     * @param array $update_appointment_request Array of the description
     * @return \PokitDok\Common\HttpResponse Response object of the booked Appointment
     * @throws \Exception On HTTP errors (status > 299)
     */
    public function update_appointment($update_appointment_uuid, array $update_appointment_request)
    {
        $this->request(
            'PUT',
            self::POKITDOK_PLATFORM_API_ENDPOINT_APPOINTMENTS. $update_appointment_uuid,
            $update_appointment_request,
            "application/json"
        );

        return $this->applyResponse();
    }

    /**
     * Cancel an Appointment.
     * See docs here: https://platform.pokitdok.com/
     *
     * @param string $cancel_appointment_uuid The Appointment UUID (pd_appointment_uuid) to cancel
     * @return \PokitDok\Common\HttpResponse Response object of the booked Appointment
     * @throws \Exception On HTTP errors (status > 299)
     */
    public function cancel_appointment($cancel_appointment_uuid)
    {
        $this->request(
            'DELETE',
            self::POKITDOK_PLATFORM_API_ENDPOINT_APPOINTMENTS,
            $cancel_appointment_uuid
        );
    }

    /**
     * The Medical Procedure Code resource provides access to clinical and consumer friendly information related to
     * medical procedures.
     * See docs here: https://platform.pokitdok.com/
     *
     * @param mixed $medical_procedure_code Optional, String of a specific procedure code OR an Array of query params.
     *      Query Parameters:
     *          name, Search medical procedure information by consumer friendly name
     *          description, A partial or full description to be used to locate medical procedure information
     * @return \PokitDok\Common\HttpResponse Response object with medical procedure code data
     * @throws \Exception
     */
    public function medical_procedure_code($medical_procedure_code = '')
    {
        $this->request(
            'GET',
            self::POKITDOK_PLATFORM_API_ENDPOINT_MPC,
            $medical_procedure_code,
            "application/json"
        );

        return $this->applyResponse();
    }

    /**
     * Submit a continuity of care document (CCD) request
     * See docs here: https://platform.pokitdok.com/
     *
     * @param array $ccd_request Required, array representing a CCD request
     * @return \PokitDok\Common\HttpResponse Response object
     * @throws \Exception
     */
    public function ccd(array $ccd_request)
    {
        $this->request(
            'POST',
            self::POKITDOK_PLATFORM_API_ENDPOINT_CCD,
            $ccd_request,
            "application/json"
        );

        return $this->applyResponse();
    }

    /**
     * The ICD Convert resource will return Diagnosis Mapping information by code (ICD-9 or ICD-10)
     * See docs here: https://platform.pokitdok.com/
     *
     * @param mixed $diagnosis_code Required, String of a specific diagnosis code
     * @return \PokitDok\Common\HttpResponse Response object Diagnosis Mapping information by code (ICD-9 or ICD-10),
     * @throws \Exception
     */
    public function icd_convert($diagnosis_code)
    {
        $this->request(
            'GET',
            self::POKITDOK_PLATFORM_API_ENDPOINT_ICD_CONVERT,
            $diagnosis_code,
            "application/json"
        );

        return $this->applyResponse();
    }

    /**
     * Submit X12 formatted 837 EDI file and map any ICD-9 codes to ICD-10.
     * See docs here: https://platform.pokitdok.com/
     *
     * @param string $claim_file full path and filename of EDI file to submit
     * @param string $callback_url Optional notification url to be called when the asynchronous processing is complete
     * @return \PokitDok\Common\HttpResponse
     * @throws \Exception On HTTP errors (status > 299)
     */
    public function claims_convert($claim_file, $callback_url = null)
    {
        $post_params = array();
        $post_params['file'] = new \CURLFile($claim_file, "application/EDI-X12", basename($claim_file));
        if ($callback_url !== null) {
            $post_params['callback_url'] = $callback_url;
        }

        $this->request(
            'POST',
            self::POKITDOK_PLATFORM_API_ENDPOINT_CLAIMS_CONVERT,
            $post_params
        );

        return $this->applyResponse();
    }

    /**
     * Creates an identity resource
     * See docs here: https://platform.pokitdok.com/
     *
     * @param array $create_identity_request Required, array representing a identity request
     * @return \PokitDok\Common\HttpResponse Response object
     * @throws \Exception
     */
    public function create_identity(array $create_identity_request)
    {
        $this->request(
            'POST',
            self::POKITDOK_PLATFORM_API_ENDPOINT_IDENTITY,
            $create_identity_request,
            "application/json"
        );

        return $this->applyResponse();
    }

    /**
     * Update identity resource.
     *
     * @param string $update_identity_uuid The identity UUID to update
     * @param array $update_identity_request Array of the identity resource
     * @return \PokitDok\Common\HttpResponse Response object of the identity resource
     * @throws \Exception On HTTP errors (status > 299)
     */
    public function update_identity($update_identity_uuid, array $update_identity_request)
    {
        $this->request(
            'PUT',
            self::POKITDOK_PLATFORM_API_ENDPOINT_IDENTITY . $update_identity_uuid,
            $update_identity_request,
            "application/json"
        );

        return $this->applyResponse();
    }

    /**
     * Query for identity
     *      given query parameters, or get a single identity given a UUID
     * See docs here: https://platform.pokitdok.com/
     *
     * @param array|string $identity_request Array of query parameters or string of the UUID
     * @return \PokitDok\Common\HttpResponse Response object with identity resource
     * @throws \Exception On HTTP errors (status > 299)
     */
    public function identity($identity_request = '')
    {
        $this->request(
            'GET',
            self::POKITDOK_PLATFORM_API_ENDPOINT_IDENTITY,
            $identity_request,
            "application/json"
        );

        return $this->applyResponse();
    }

    /**
     * Query for identity history
     * See docs here: https://platform.pokitdok.com/
     *
     * @param string $identity_uuid string of the UUID
     * @param string $identity_version_id string of the version identifier
     * @return \PokitDok\Common\HttpResponse Response object with identity resource
     * @throws \Exception On HTTP errors (status > 299)
     */
    public function identity_history($identity_uuid, $identity_version_id = null)
    {
        $identity_path = self::POKITDOK_PLATFORM_API_ENDPOINT_IDENTITY . $identity_uuid . "/history";
        if (!is_null($identity_version_id)) {
            $identity_path .= "/" . $identity_version_id;
        }

        $this->request(
            'GET',
            $identity_path,
            null,
            "application/json"
        );

        return $this->applyResponse();
    }

    /**
     * Query for pharmacy plans
     *      given query parameters, or get a single pharmacy plans given a UUID
     * See docs here: https://platform.pokitdok.com/
     *
     * @param array|string $pharmacy_plan_request Array of query parameters or string of the UUID
     * @return \PokitDok\Common\HttpResponse Response object with identity resource
     * @throws \Exception On HTTP errors (status > 299)
     */
    public function pharmacy_plans($pharmacy_plan_request = '')
    {
        $this->request(
            'GET',
            self::POKITDOK_PLATFORM_API_ENDPOINT_PHARMACY_PLANS,
            $pharmacy_plan_request,
            "application/json"
        );

        return $this->applyResponse();
    }

    /**
     * Query for pharmacy formulary
     *      given query parameters, or get a single pharmacy formulary given a UUID
     * See docs here: https://platform.pokitdok.com/
     *
     * @param array|string $pharmacy_formulary_request Array of query parameters or string of the UUID
     * @return \PokitDok\Common\HttpResponse Response object with identity resource
     * @throws \Exception On HTTP errors (status > 299)
     */
    public function pharmacy_formulary($pharmacy_formulary_request = '')
    {
        $this->request(
            'GET',
            self::POKITDOK_PLATFORM_API_ENDPOINT_PHARMACY_FORMULARY,
            $pharmacy_formulary_request,
            "application/json"
        );

        return $this->applyResponse();
    }

    /**
     * Perform a general PokitDok Platform API request
     * See docs here: https://platform.pokitdok.com/
     *
     * @param string $request_type The HTTP verb of the request
     * @param string $request_path The URL path of the request
     * @param null $parameters Query parameters (array: formatted as query params or string: appended to URL path), or
     *      Post parameters (array representing JSON document if content is '', or array of body fields)
     * @param string $content_type
     * @return \PokitDok\Common\HttpResponse
     * @throws \Exception
     */
    public function request($request_type, $request_path, $parameters = null, $content_type = '')
    {
        parent::request($request_type, $request_path, $parameters, $content_type);

        return $this->applyResponse();
    }
}
