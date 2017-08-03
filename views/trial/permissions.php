<?php
/* @var $this TrialController */
/* @var $model Trial
 * @var UserTrialPermission $newPermission
 * @var CDataProvider $permissionDataProvider
 */

$canManageTrial = Trial::checkTrialAccess(Yii::app()->user, $model->id, UserTrialPermission::PERMISSION_MANAGE);
?>

  <h1 class="badge">Trial Sharing</h1>
  <div class="row">
    <div class="large-9 column">

      <div class="box admin">
        <div class="row">
          <div class="large-9 column">
            <h1 style="display: inline">Users shared with <?php echo CHtml::encode($model->name); ?></h1>
          </div>
        </div>

        <div class="row">
          <div class="large-9 column">
            <table id="currentPermissions">
              <thead>
              <tr>
                <th>User</th>
                  <th>User Role</th>
                  <th>Permission</th>
                    <?php if ($canManageTrial): ?>
                      <th>Actions</th>
                    <?php endif; ?>
                </tr>
                </thead>
              <tbody>
              <?php $this->widget('zii.widgets.CListView', array(
                  'id' => 'permissionList',
                  'dataProvider' => $permissionDataProvider,
                  'itemView' => '/userTrialPermission/_view',
              )); ?>
              </tbody>
            </table>
          </div>
        </div>

        <?php if ($canManageTrial): ?>
        <h2>Share with another user:</h2>
        <div class="row field-row">
          <div class="large-6 column">

            <div class="row field-row">
              <div class="large-3 column">
                <?php echo CHtml::activeLabel($newPermission, 'user'); ?>
              </div>
              <div class="large-6 column end">

              <?php  $this->widget('zii.widgets.jui.CJuiAutoComplete', array(
                    'name' => 'user_id',
                    'id' => 'autocomplete_user_id',
                    'source' => "js:function(request, response) {
                                        $.getJSON('" . $this->createUrl('userAutoComplete') . "', {
                                            id : $model->id,
                                            term : request.term
                                        }, response);
                                }",
                    'options' => array(
                        'select' => "js:function(event, ui) {
                                        removeSelectedUser();
                                        addItem('selected_user_wrapper', ui);
                                        $('#autocomplete_user_id').val($('#user_name').text());
                                        return false;
                        }",
                        'response' => 'js:function(event, ui){
                            if(ui.content.length === 0){
                                $("#no_user_result").show();
                            } else {
                                $("#no_user_result").hide();
                            }
                        }',
                    ),
                    'htmlOptions' => array(
                        'placeholder' => 'search Users',
                    ),
                )); ?>
            </div>
          </div>

          <div class="row field-row">
            <div class="large-3 column">
              <?php echo CHtml::activeLabel($newPermission, 'role'); ?>
             </div>
              <div class="large-6 column end">
                <?php echo CHtml::activeTelField($newPermission, 'role', array('size' => 30, 'maxlength' => 255, 'name' => 'user_role')); ?>
              </div>
            </div>
            <div class="row field-row">
              <div class="large-3 column">
                  <?php echo CHtml::activeLabel($newPermission, 'permission'); ?>
              </div>
              <div class="large-6 column end">
                <?php echo CHtml::dropDownList('permission', 'Select One...', UserTrialPermission::getPermissionOptions(),
                    array('id' => 'permission')); ?>
              </div>
            </div>

             <div class="row field-row">
            <div id="selected_user_wrapper" class="large-8 column <?php echo !$newPermission->user_id ? 'hide' : '' ?>">
              <button class="secondary small btn_save_permission">Share with 
			    <span id="user_name">
	              <?php echo CHtml::encode($newPermission->user_id ? $newPermission->user->getFullName() : ''); ?>
				</span>
              </button>
              &nbsp;
              <a href="javascript:void(0)" class="button event-action cancel small"
                 onclick="removeSelectedUser()">Clear</a>
                <?php echo CHtml::hiddenField('user_id', $newPermission->user_id, array('class' => 'hidden_id')); ?>
            </div>
          </div>

        </div>
      </div>
        <?php echo CHtml::hiddenField('trial_id', $model->id, array('class' => 'hidden_id')); ?>

      <div class="alert-box info with-icon">
        Can't find the user you're looking for? They might not have the permission to view trials.
        <br/>
        Please contact an administrator and ask them to give that user the "View Trial" role.
      </div>

      <?php endif; ?>
  </div>
</div>

<?php if ($canManageTrial): ?>
  <!-- Confirm permission deletion dialog (copied from allergy dialog)-->
  <div id="confirm_remove_permission_dialog" title="Confirm remove permission" style="display: none;">
    <div id="delete_permission">
      <p>
        <strong>Are you sure you want to proceed?</strong>
      </p>
      <div class="buttons">
        <input type="hidden" id="remove_permission_id" value=""/>
        <button type="submit" class="warning small btn_remove_permission">Remove permission</button>
        <button type="submit" class="secondary small btn_cancel_remove_permission">Cancel</button>
        <img class="loader" src="<?php echo Yii::app()->assetManager->createUrl('img/ajax-loader.gif') ?>"
             alt="loading..." style="display: none;"/>
      </div>
    </div>
  </div>

  <script type="text/javascript">
    function addItem(wrapper_id, ui) {
      var $wrapper = $('#' + wrapper_id);

      $('#user_name').text(ui.item.label);
      $wrapper.show();
      $wrapper.find('.hidden_id').val(ui.item.id);
    }

    function removeSelectedUser() {
      $('#no_user_result').hide();
      $('#user_name').text('');
      $('#selected_user_wrapper').hide();
      $('#autocomplete_user_id').val('');
    }

    $(document).ready(function () {

      $('#selected_user_wrapper').on('click', '.remove', function () {
        removeSelectedUser();
      });

      $('button.btn_save_permission').click(function () {
        if ($('#user_id').val() == '') {
          new OpenEyes.UI.Dialog.Alert({
            content: "Please select a user and a permission level"
          }).open();
          return false;
        }

        $.ajax({
          'type': 'GET',
          'url': '<?php echo $this->createUrl('addPermission'); ?>',
          'data': {
            id: <?php echo $model->id; ?>,
            user_id: $('#user_id').val(),
            permission: $('#permission').val(),
            role: $('#user_role').val()
          },
          'success': function (html) {
            if (html === '<?php echo Trial::RETURN_CODE_USER_PERMISSION_ALREADY_EXISTS; ?>') {
              new OpenEyes.UI.Dialog.Alert({
                content: "That patient has already been shared to this trial. To change their permissions, please remove them first and try again."
              }).open();
            } else if (html === '<?php echo Trial::RETURN_CODE_USER_PERMISSION_OK; ?>') {
              location.reload();
            } else {
              new OpenEyes.UI.Dialog.Alert({
                content: "An unknown response code was returned by the system: " + html + "\n\nPlease contact support for assistance."
              }).open();
            }
          }
          ,
          'error': function () {
            new OpenEyes.UI.Dialog.Alert({
              content: "Sorry, an internal error occurred and we were unable to remove the permission.\n\nPlease contact support for assistance."
            }).open();
          }
        })
        ;

        return false;
      });

    });

    $('.removePermission').live('click', function () {
      $('#remove_permission_id').val($(this).attr('rel'));

      $('#confirm_remove_permission_dialog').dialog({
        resizable: false,
        modal: true,
        width: 560
      });

      return false;
    });

    $('button.btn_remove_permission').click(function () {
      $("#confirm_remove_permission_dialog").dialog("close");

      var permission_id = $('#remove_permission_id').val();

      $.ajax({
        'type': 'GET',
        'url': baseUrl + '<?php echo Yii::app()->controller->createUrl('/OETrial/trial/removePermission',
            array('id' => $model->id)); ?>' + '?permission_id=' + permission_id,
        'success': function (result) {
          if (result === '<?php echo Trial::REMOVE_PERMISSION_RESULT_SUCCESS; ?>') {
            var row = $('#currentPermissions tr[data-permission-id="' + permission_id + '"]');
            row.hide('slow', function () {
              row.remove();
            });
          } else if (result === '<?php echo Trial::REMOVE_PERMISSION_RESULT_CANT_REMOVE_SELF; ?>') {
            new OpenEyes.UI.Dialog.Alert({
              content: "You can't remove yourself from this Trial.\n\nYou will have to get another user with Manage privileges to remove you."
            }).open();
          } else if (result === '<?php echo Trial::REMOVE_PERMISSION_RESULT_CANT_REMOVE_LAST; ?>') {
            new OpenEyes.UI.Dialog.Alert({
              content: "You can't remove the last user from the Trial.\n\nThere must always be at least one person assigned to a Trial."
            }).open();
          } else {
            new OpenEyes.UI.Dialog.Alert({
              content: "Sorry, an internal error occurred and we were unable to remove the permission.\n\nPlease contact support for assistance."
            }).open();
          }
        },
        'error': function () {
          new OpenEyes.UI.Dialog.Alert({
            content: "Sorry, an internal error occurred and we were unable to remove the permission.\n\nPlease contact support for assistance."
          }).open();
        }
      });

      return false;
    });

    $('button.btn_cancel_remove_permission').click(function () {
      $("#confirm_remove_permission_dialog").dialog("close");
      return false;
    });

  </script>
<?php endif; ?>