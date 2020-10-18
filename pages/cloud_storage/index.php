<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele

use \system\classes\Core;
use \system\classes\Configuration;
use \system\packages\duckietown_storage\DuckietownStorage;

require_once $GLOBALS['__SYSTEM__DIR__'] . 'templates/modals/SmartFormModal.php';

// get all buckets and actions
$_all_buckets = DuckietownStorage::STORAGE_BUCKETS;
$_all_actions = DuckietownStorage::STORAGE_ACTIONS;

// get arguments
$arg_bucket = (isset($_GET['bucket']) && in_array($_GET['bucket'], $_all_buckets))?
    $_GET['bucket'] : null;
$arg_action = (isset($_GET['action']) && in_array($_GET['action'], $_all_actions))?
    $_GET['action'] : null;
$arg_object = isset($_GET['object'])? $_GET['object'] : null;

// user and group filters are null
$_is_admin = Core::getUserRole() === 'administrator';
$_user = $_is_admin ? null : Core::getUserLogged('username');
$_groups = [null];
if (!is_null($_user)) {
    $_groups = [];
    $res = Core::getUserGroups($_user);
    if (!$res['success']) {
        Core::throwError($res['data']);
        return;
    }
    $_groups = $res['data'];
}

// get all object permissions
$objects = DuckietownStorage::getStorageSpacePermissionsForUser($_user, $arg_bucket, $arg_object, $arg_action);
foreach ($_groups as $_group) {
    $objects = array_merge_recursive(
        $objects,
        DuckietownStorage::getStorageSpacePermissionsForGroup($_group, $arg_bucket, $arg_object, $arg_action)
    );
}

// reorganize all objects
$_all_objects = [];
foreach ($objects as $bucket => &$bkt_objects) {
    foreach ($bkt_objects as $object => &$_) {
        array_push($_all_objects, $object);
    }
}
$_all_objects = array_values(array_unique($_all_objects));
sort($_all_buckets);

// get all users / groups
$_all_users = Core::getUsersList();
$_all_users_labels = [''];
foreach ($_all_users as $uid) {
    $res = Core::getUserInfo($uid);
    if (!$res['success']) {
        Core::throwError($res['data']);
    }
    array_push($_all_users_labels, $res['data']['name']);
}
$_all_groups = Core::getGroupsList();
$_all_groups_labels = [''];
foreach ($_all_groups as $gid) {
    $res = Core::getGroupInfo($gid);
    if (!$res['success']) {
        Core::throwError($res['data']);
    }
    array_push($_all_groups_labels, $res['data']['name']);
}

// create schema for new permission form
$form_schema = [
    'type' => 'form',
    'details' => 'New permission form',
    '_data' => [
        'bucket' => [
            'type' => 'enum',
            'details' => 'Target Bucket of the new permissions to grant',
            'values' => $_all_buckets
        ],
        'action' => [
            'type' => 'enum',
            'details' => 'Action to allow on the given Object',
            'values' => $_all_actions
        ],
        'object' => [
            'type' => 'text',
            'details' => 'Path to the object the Action can be performed on.
                Use the wildcard <strong>*</strong> to define path patterns.'
        ],
        'uid' => [
            'type' => 'enum',
            'details' => 'User you want to grant the new permission to. (Do not use together with Group)',
            'values' => array_merge(['null'], $_all_users),
            '__form__' => [
                'title' => 'User',
                'labels' => $_all_users_labels
            ]
        ],
        'gid' => [
            'type' => 'enum',
            'details' => 'Group you want to grant the new permission to. (Do not use together with User)',
            'values' => array_merge(['null'], $_all_groups),
            '__form__' => [
                'title' => 'Group',
                'labels' => $_all_groups_labels
            ]
        ]
    ]
];
$form = new SmartFormModal($form_schema);
?>

<h2 class="page-title"></h2>
<?php
if ($_is_admin) {
    ?>
    <button
        class="btn btn-warning btn-sm"
        type="button"
        data-toggle="tooltip dialog"
        data-placement="bottom"
        data-original-title="Grant new permissions"
        data-modal-mode="insert"
        data-modal-title="Grant new Cloud Storage permissions"
        data-target="#<?php echo $form->modalID ?>"
        style="float: right">
        <i class="fa fa-plus" aria-hidden="true"></i>
        &nbsp;Grant permission
    </button>
    <?php
}
?>

<p>
    This page lets you manage access to the Duckietown Storage Space.
</p>
<br/>

<form class="form-inline" id="_log_selectors_form">

    <div class="row">

        <div class="_selector col-md-3" style="width: 33%">
            <div class="form-group">
                <div class="input-group">
                    <div class="input-group-addon">
                        <i class="fa fa-bitbucket" aria-hidden="true"></i> &nbsp;Bucket
                    </div>
                    <select id="_sel_bucket" class="selectpicker" data-live-search="true"
                            data-width="100%" multiple data-max-options="1">
                        <?php
                        foreach ($_all_buckets as $bucket) {
                            $sel = ($bucket == $arg_bucket)? 'selected' : '';
                            ?>
                            <option value="<?php echo $bucket ?>" <?php echo $sel ?>>
                                <?php echo $bucket ?>
                            </option>
                            <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="_selector col-md-3" style="width: 33%">
            <div class="form-group">
                <div class="input-group">
                    <div class="input-group-addon">
                        <i class="fa fa-code" aria-hidden="true"></i> &nbsp;Action
                    </div>
                    <select id="_sel_action" class="selectpicker" data-live-search="true"
                            data-width="100%" multiple data-max-options="1">
                        <?php
                        foreach ($_all_actions as $action) {
                            $sel = ($action == $arg_action)? 'selected' : '';
                            ?>
                            <option value="<?php echo $action ?>" <?php echo $sel ?>>
                                <?php echo $action ?>
                            </option>
                            <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="_selector col-md-3" style="width: 33%">
            <div class="form-group">
                <div class="input-group">
                    <div class="input-group-addon">
                        <i class="fa fa-file" aria-hidden="true"></i> &nbsp;Object
                    </div>
                    <select id="_sel_object" class="selectpicker" data-live-search="true"
                            data-width="100%" multiple data-max-options="1">
                        <?php
                        foreach ($_all_objects as $object) {
                            $sel = ($object == $arg_object)? 'selected' : '';
                            ?>
                            <option value="<?php echo $object ?>" <?php echo $sel ?>>
                                <?php echo $object ?>
                            </option>
                            <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>

    </div>
</form>
<br/>
<br/>

<h4>
    Granted Permissions:
</h4>
<br/>

<?php
$grantee_info_cache = [
    'user' => [],
    'group' => []
];

function _grantee_name(&$permission) {
    global $grantee_info_cache;
    if (array_key_exists($permission['grantee-id'], $grantee_info_cache[$permission['grantee-type']])) {
        return $grantee_info_cache[$permission['grantee-type']][$permission['grantee-id']];
    }
    $name = '(unknown)';
    if ($permission['grantee-type'] == 'user') {
        $res = Core::getUserInfo($permission['grantee-id']);
        $name = $res['success']? $res['data']['name'] : '(error)';
    }
    if ($permission['grantee-type'] == 'group') {
        $res = Core::getGroupInfo($permission['grantee-id']);
        $name = $res['success']? $res['data']['name'] : '(error)';
    }
    $grantee_info_cache[$permission['grantee-type']][$permission['grantee-id']] = $name;
    return $name;
}

function _action_description($action) {
    return ucfirst(str_replace('_', ' ', $action));
}

if (count($objects) == 0) {
    ?>
    <h4 class="text-center" style="margin: 20px 0">(no results)</h4>
    <?php
}

foreach ($objects as $bucket => &$bkt_objects) {
    ksort($bkt_objects);
    foreach ($bkt_objects as $object => &$object_permissions) {
        ?>
        <div class="panel panel-default">
            <div class="panel-body">
                <i class="fa fa-file" aria-hidden="true"></i> &nbsp;
                <span style="font-family: monospace">
                    <?php echo $object ?>
                </span>
                <span style="float:right;">
                    <i class="fa fa-bitbucket" aria-hidden="true"></i> &nbsp;
                    <?php echo $bucket ?>
              </span>
            </div>
            <div class="panel-footer">
                <div class="container-fluid">
                <div class="row">
                    <?php
                    ksort($object_permissions);
                    foreach ($object_permissions as $action => $permissions) {
                        ?>
                        <div class="col-md-6" style="display: inline-block">
                            <dl class="dl-horizontal" style="margin: 20px 0;">
                                <dt>Action</dt>
                                <dd><kbd><?php echo $action ?></kbd></dd>
                                <dt>Description</dt>
                                <dd><?php echo _action_description($action) ?></dd>
                                <dt>- - - - - - - - - -</dt>
                                <dd></dd>
                                <dt>Granted to:</dt>
                                <?php
                                foreach ($permissions as &$permission) {
                                    ?>
                                    <dd>
                                        <i class="fa fa-<?php echo $permission['grantee-type'] ?>" aria-hidden="true"></i> &nbsp;
                                        <?php echo _grantee_name($permission) ?>
                                        &nbsp;|&nbsp;
                                        <a href="#" onclick="_revoke_permission(this)"
                                            data-bucket="<?php echo $bucket ?>"
                                            data-action="<?php echo $action ?>"
                                            data-object="<?php echo $object ?>"
                                            data-grantee-type="<?php echo $permission['grantee-type'] ?>"
                                            data-grantee-id="<?php echo $permission['grantee-id'] ?>"
                                        >
                                            <i class="fa fa-times" aria-hidden="true" style="color:red"></i>
                                        </a>
                                    </dd>
                                    <dt></dt>
                                    <?php
                                }
                                ?>
                                <dd></dd>
                            </dl>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                </div>
            </div>
        </div>
        <?php
    }
}

// render the "new permission" form
$form->render();
?>



<script type="text/javascript">
    
    function _revoke_permission(target) {
        let grantee_type = $(target).data('grantee-type');
        let grantee_id = $(target).data('grantee-id');
        let grantee_key = (grantee_type === 'user')? 'uid' : 'gid';
        // prepare arguments
        let args = {
            'bucket': $(target).data('bucket'),
            'action': $(target).data('action'),
            'object': $(target).data('object')
        };
        args[grantee_key] = grantee_id;
        // ---
        openYesNoModal('Are you sure you want to revoke this permission?', function () {
            // call API
            smartAPI('duckietown_storage', 'revoke_permission', {
                'arguments': args,
                'reload': true,
                'block': true,
                'confirm': true
            });
        });
    }
    
    function _apply_filters(){
        let qs = {};
        ['bucket', 'action', 'object'].forEach(function(e){
            let val = $('#_sel_{0}'.format(e)).val();
            if (val !== null) {
                qs[e] = val[0];
            }
        });
        qs = $.param(qs);
        // ---
        window.location.href = "<?php echo Core::getURL(Configuration::$PAGE, '?{0}') ?>".format(qs);
    }
    
    $('#_sel_bucket').on('changed.bs.select', _apply_filters);
    
    $('#_sel_action').on('changed.bs.select', _apply_filters);
    
    $('#_sel_object').on('changed.bs.select', _apply_filters);
    
    $(window).on("<?php echo $form->onSaveEvent ?>", function(evt, data){
        // call API with the serialized form
        smartAPI(
            'duckietown_storage',
            'grant_permission',
            {
                'method': 'POST',
                'data': data,
                'block': true,
                'confirm': true,
                'reload': true
            }
        );
    });
    
</script>

