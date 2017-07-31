<?php
/* @var TrialController $this */
/* @var Trial $trial */
/* @var CActiveDataProvider $dataProvider */
/* @var string $listId */
/* @var string $title */
/* @var int $sort_by */
/* @var int $sort_dir */
?>

<?php
if ((int)$dataProvider->getTotalItemCount() === 0): ?>
  <h2>
      <?php echo $title; ?>
  </h2>
  <p>
    There are no <?php echo $title; ?> to display.
  </p>
<?php else: ?>
    <?php
    $dataProvided = $dataProvider->getData();
    $items_per_page = $dataProvider->getPagination()->getPageSize();
    $page_num = $dataProvider->getPagination()->getCurrentPage();
    $from = ($page_num * $items_per_page) + 1;
    $to = min(($page_num + 1) * $items_per_page, $dataProvider->totalItemCount);
    ?>
  <h2>
      <?php echo $title; ?>: viewing <?php echo $from; ?> - <?php echo $to; ?>
    of <?php echo $dataProvider->totalItemCount ?>
  </h2>

  <table id="patient-grid" class="grid">
    <thead>
    <tr>
        <?php
        $columns = array(
            '',
            'Name',
            'Gender',
            'Age',
            'Ethnicity',
            'External Reference',
        );

        if ((int)$trial->trial_type === Trial::TRIAL_TYPE_INTERVENTION) {
            $columns[] = 'Treatment Type';
        }

        $columns[] = 'Diagnoses/Medication';
        $columns[] = 'Actions';

        $sortableColumns = array('Name', 'Gender', 'Age', 'Ethnicity', 'External Reference', 'Treatment Type');

        foreach ($columns as $i => $field): ?>
          <th id="patient-grid_c<?php echo $i; ?>">

              <?php if (in_array($field, $sortableColumns, true)): ?>
                  <?php
                  $new_sort_dir = ($i === $sort_by) ? 1 - $sort_dir : 0;
                  $sort_symbol = '';
                  if ($i === $sort_by) {
                      $sort_symbol = $sort_dir === 1 ? '&#x25BC;' /* down arrow */ : '&#x25B2;'; /* up arrow */
                  }

                  echo CHtml::link(
                      $field . $sort_symbol,
                      $this->createUrl('view',
                          array(
                              'id' => $trial->id,
                              'sort_by' => $i,
                              'sort_dir' => $new_sort_dir,
                              'page_num' => $page_num,
                          ))
                  );
                  ?>
              <?php else: ?>
                  <?php echo $field; ?>
              <?php endif; ?>
          </th>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>

    <?php /* @var Trial $trial */
    foreach ($dataProvided as $i => $trialPatient): ?>
        <?php $this->renderPartial('/trialPatient/_view', array('data' => $trialPatient)); ?>
    <?php endforeach; ?>
    </tbody>
    <tfoot class="pagination-container">
    <tr>
      <td colspan="9">
          <?php
          $this->widget('LinkPager', array(
              'pages' => $dataProvider->getPagination(),
              'maxButtonCount' => 15,
              'cssFile' => false,
              'selectedPageCssClass' => 'current',
              'hiddenPageCssClass' => 'unavailable',
              'htmlOptions' => array(
                  'class' => 'pagination',
              ),
          ));
          ?>
      </td>
    </tr>
    </tfoot>
  </table>
<?php endif; ?>
