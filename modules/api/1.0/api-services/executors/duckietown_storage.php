<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele


use \system\packages\duckietown_storage\DuckietownStorage;
use \system\classes\Core;


function execute(&$service, &$actionName, &$arguments) {
    $action = $service['actions'][$actionName];
    Core::startSession();
    //
    switch ($actionName) {
        case 'has_permission':
            // get permissions for user with UID
            $res = DuckietownStorage::checkStorageSpacePermissions(
                $arguments['uid'], $arguments['bucket'], $arguments['object'], $arguments['action']
            );
            if (!$res['success']) {
                return response400BadRequest($res['data']);
            }
            // return allowed actions
            return response200OK(['granted' => $res['data']]);
            break;
        //
        case 'grant_permission':
            // make sure we have exactly one argument between uid and gid
            $res = _parse_uid_gid($arguments);
            if (!$res['success']) {
                return response400BadRequest($res['data']);
            }
            // we have either uid or gid
            if ($res['data']['type'] == 'uid') {
                $res = DuckietownStorage::grantStorageSpacePermissionToUser(
                    $res['data']['id'], $arguments['bucket'], $arguments['object'],
                    $arguments['action']
                );
            } else {
                $res = DuckietownStorage::grantStorageSpacePermissionToGroup(
                    $res['data']['id'], $arguments['bucket'], $arguments['object'],
                    $arguments['action']
                );
            }
            // on error
            if (!$res['success']) {
                return response400BadRequest($res['data']);
            }
            // success
            return response200OK();
            break;
        //
        case 'revoke_permission':
            // make sure we have exactly one argument between uid and gid
            $res = _parse_uid_gid($arguments);
            if (!$res['success']) {
                return response400BadRequest($res['data']);
            }
            // we have either uid or gid
            if ($res['data']['type'] == 'uid') {
                $res = DuckietownStorage::revokeStorageSpacePermissionToUser(
                    $res['data']['id'], $arguments['bucket'], $arguments['object'],
                    $arguments['action']
                );
            } else {
                $res = DuckietownStorage::revokeStorageSpacePermissionToGroup(
                    $res['data']['id'], $arguments['bucket'], $arguments['object'],
                    $arguments['action']
                );
            }
            // on error
            if (!$res['success']) {
                return response400BadRequest($res['data']);
            }
            // success
            return response200OK();
            break;
        //
        default:
            return response404NotFound(sprintf("The command '%s' was not found", $actionName));
            break;
    }
}//execute


function _parse_uid_gid(&$arguments) {
    $uid = null;
    $gid = null;
    // get UID
    if (isset($arguments['uid']) && strlen(trim($arguments['uid'])) > 0) {
        $uid = $arguments['uid'];
    }
    // get GID
    if (isset($arguments['gid']) && strlen(trim($arguments['gid'])) > 0) {
        $gid = $arguments['gid'];
    }
    // check if at least one is given
    if (is_null($uid) && is_null($gid)) {
        return [
            'success' => false,
            'data' => "At least one of 'uid' and 'gid' must be specified."
        ];
    }
    // check if they were both given
    if (!is_null($uid) && !is_null($gid)) {
        return [
            'success' => false,
            'data' => "Both 'uid' and 'gid' were given, you cannot use both at the same time."
        ];
    }
    // return UIG or GID
    if (!is_null($uid)) {
        return ['success' => true, 'data' => ['type' => 'uid', 'id' => $uid]];
    }
    return ['success' => true, 'data' => ['type' => 'gid', 'id' => $gid]];
}

?>
