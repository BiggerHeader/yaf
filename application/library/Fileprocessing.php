<?php

/**
 * 文件处理类
 */
class Fileprocessing
{
    // 获取文件基本信息
    public function getFileBase($id = null, $type = null)
    {
        if (is_null($id) || is_null($type)) {
            return null;
        }
        
        $api = new TZ_Api();
        
        if ($type == 'storeCheck') {
            // 企业认证图片，调用垫付宝接口
            $response = $api::getInstance()->get('getKpayFileMeta', $id)->call();
            if ($response->apiOk && isset($response->body) && isset($response->body->data)) {
                return $response->body->data;
            }
        }
        
        return null;
    }
    
    // 获取文件内容
    public function getFileBody($id = null, $type = null)
    {
        if (is_null($id) || is_null($type)) {
            return null;
        }
        
        $api = new TZ_Api();
        
        if ($type == 'storeCheck') {
            // 企业认证图片，调用会员中心接口（兼容轻易贷图片）
            $response = $api::getInstance()->get('userFileBinary', $id)->call();
            if ($response->apiOk) {
                return $response->body;
            }
        }
        
        return null;
    }
    
    // 上传文件到swift服务器
    public function uploadFileToSwift($info = array(), $fileBody = null, $type = null)
    {
        if (empty($info) || is_null($fileBody) || is_null($type)) {
            return null;
        }
        
        $api = new TZ_Api();
        
        // 企业认证图片，调用垫付宝接口
        if ($type == 'storeCheck') {
            $tags = array();
            if (isset($info['fileName'])) {
                $tags[] = 'originalName:' . $info['fileName'];
            }
            $metaBody = array(
                'visibility' => 0,
                'tags' => $tags,
                'name' => $info['saveName'],
                'type' => $info['fileType'],
                'size' => $info['fileSize'],
                'platFormType' => 4
            );
            // 创建文件元数据信息
            $metaResp = $api::getInstance()->post('kpayFileMeta', null, $metaBody)->call();
            if ($metaResp->apiOk) {
                $metaId = $metaResp->body->data->id;
                // 上传文件
                $binaryResp = $api::getInstance()->upload('uploadKpayFileBinary', $metaId, $fileBody, null)->call();
                if ($binaryResp->apiOk) {
                    return $metaId;
                }
            }
        } else { // 壹号车文件服务器接口
            $responseHeader = array(
                'Content-Type' => $info['fileType']
            );
            $plat = Yaf_Registry::get('config')->che001File->plat;
            $response = $api::getInstance()->upload('che001UploadRaw', null, $fileBody, '?plat=' . $plat)
                ->headers($responseHeader)
                ->call();
            
            if ($response->apiOk && isset($response->body) && isset($response->body->errcode) && $response->body->errcode == 0) {
                if (isset($response->body->data) && isset($response->body->data->data)) {
                    return $response->body->data->data;
                }
            }
        }
        
        return null;
    }
}
