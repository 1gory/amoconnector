<?php

class AmoCrmConnector
{
    /** @var string */
    private $cookiePath;

    /** @var string */
    private $apiKey;

    /** @var string */
    private $login;

    /** @var string */
    private $subdomain;

    /**
     * AmoCrmConnector constructor.
     * @param $login
     * @param $apiKey
     * @param $subdomain
     */
    public function __construct($login, $apiKey, $subdomain)
    {
        $this->login = $login;
        $this->apiKey = $apiKey;
        $this->subdomain = $subdomain;
        $this->cookiePath = dirname(__FILE__) . '/cookie.txt';
    }

    /**
     * @return bool
     */
    public function authentication()
    {
        $user = [
            'USER_LOGIN' => $this->login,
            'USER_HASH' => $this->apiKey,
        ];

        $url = 'https://' . $this->subdomain . '.amocrm.ru/private/api/auth.php?type=json';
        $out = $this->sendCurlRequest($user, $url);

        $response = json_decode($out, true);
        $response = $response['response'];
        if (isset($response['auth']) && $response['auth'] === true) {
            return true;
        }

        return false;
    }

    /**
     * @param string $leadName
     * @param string $price
     * @param array $customFields
     * @throws Exception
     */
    public function createLead($leadName, $price, $customFields)
    {
        $leads['request']['leads']['add'] = [
            [
                'name' => $leadName,
                'price' => $price,
                'date_create' => (new \DateTime())->format('U'),
                'custom_fields' => $customFields,
            ],
        ];

        $url = 'https://' . $this->subdomain . '.amocrm.ru/private/api/v2/json/leads/set';
        $out = $this->sendCurlRequest($leads, $url);

        $response = json_decode($out, true);

        $response = $response['response'];

        return $response['leads']['add'][0]['id'];
    }

    /**
     * @param $orderId
     * @param $contactName
     * @param $customFields
     * @return mixed
     * @throws Exception
     */
    public function createContact($orderId, $contactName, $customFields)
    {
        $contacts['add'] = [
            [
                'name' => $contactName,
                'created_at' => (new \DateTime())->format('U'),
                'leads_id' => [
                    (string)$orderId,
                ],
                'custom_fields' => $customFields,
            ],
        ];

        $url = 'https://' . $this->subdomain . '.amocrm.ru/api/v2/contacts';

        $out = $this->sendCurlRequest($contacts, $url);

        $response = json_decode($out, true);
        $response = $response['response'];

        return $response;
    }

	/**
	 * @param $orderId
	 * @param $message
	 * @return null
	 * @throws Exception
	 */
    public function createNote($orderId, $message)
    {
        $notes = ['add' => []];

        $notes['add'][] = [
            'element_id' => $orderId,
            'element_type' => '2',
            'text' => $message,
            'note_type' => '4',
            'created_at' => (new \DateTime())->format('U'),
        ];

        $url = 'https://' . $this->subdomain . '.amocrm.ru/api/v2/notes';

        if (empty($notes)) {
            return null;
        }

        $this->sendCurlRequest($notes, $url);

//        $out = $this->sendCurlRequest($notes, $url);
//        $response = json_decode($out, true);
//        $response = $response['response']
//        return $response;

		return ;
    }

    private function sendCurlRequest($data, $url)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookiePath);
        curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookiePath);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

        $out = curl_exec($curl);
        curl_close($curl);

        return $out;
    }
}
