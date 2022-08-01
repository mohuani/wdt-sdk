<?php

namespace App\Services;

use Exception;
use stdClass;

/**
 * Class WdtClientService
 * @package App\Services
 * User: mohuani
 * Date：2021/10/21 10:10
 */
class WdtClientService {
    public string $sid;
    public string $appkey;
    public string $appsecret;
    public string $gatewayUrl = "";
    private array $apiParas = [];

    /**
     * @param $req
     * @return string
     */
    private function packData (&$req): string {
        ksort($req);
        $arr = [];
        foreach ($req as $key => $val) {
            if ($key == 'sign') continue;
            if (count($arr))
                $arr[] = ';';
            $arr[] = sprintf("%02d", iconv_strlen($key, 'UTF-8'));
            $arr[] = '-';
            $arr[] = $key;
            $arr[] = ':';
            $arr[] = sprintf("%04d", iconv_strlen($val, 'UTF-8'));
            $arr[] = '-';
            $arr[] = $val;
        }
        return implode('', $arr);
    }

    //加密生成sign
    private function makeSign (&$req, $appsecret) {
        $sign = md5($this->packData($req).$appsecret);
        $req['sign'] = $sign;
    }

    /**
     * @throws Exception
     */
    private function check () {
        //请求参数校验
        if ($this->checkEmpty($this->sid))
            throw new Exception('缺少必要请求参数【 sid 】', 40);
        if ($this->checkEmpty($this->appkey))
            throw new Exception('缺少必要请求参数【 appkey 】', 41);
        if ($this->checkEmpty($this->appsecret))
            throw new Exception('【 appsecret 】 未填写', 42);
    }

    private function checkEmpty ($value): bool {
        if (!isset($value)) {
            return true;
        }
        if ($value === null) {
            return true;
        }
        if (is_array($value) && count($value) == 0) {
            return true;
        }
        if (is_string($value) && trim($value) === "") {
            return true;
        }
        return false;
    }

    /**
     * @throws Exception
     */
    public function putApiParam ($key, $value) {
        if ($this->checkEmpty($value))
            throw new Exception("传入参数【 $key 】 值为空", 46);
        $this->apiParas[$key] = $value;
    }

    public function putMultiApiparam ($params) {
        $this->apiParas = array_merge($this->apiParas, $params);

    }

    protected function wdtOpenApi () {
        $result = new stdClass();
        //参数校验
        try {
            $this->check();
        } catch (Exception $e) {
            $result->code = $e->getCode();
            $result->msg = $e->getMessage();
            return $result;
        }

        //参数封装
        $this->apiParas['sid'] = $this->sid;
        $this->apiParas['appkey'] = $this->appkey;
        $this->apiParas['timestamp'] = time();

        $this->makeSign($this->apiParas, $this->appsecret);
    }

    /**
     * @return string
     */
    public function getRequestParams (): string {
        $this->wdtOpenApi();
        return http_build_query($this->apiParas, '', '&');
    }
}