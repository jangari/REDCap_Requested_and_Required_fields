<?php namespace INTERSECT\RequestedAndRequiredFields;

use \REDCap as REDCap;
use ExternalModules\AbstractExternalModule;

class RequestedAndRequiredFields extends \ExternalModules\AbstractExternalModule {

    function getTags($tags, $fields, $instruments) {
        // This is straight out of Andy Martin's example post on this:
        // https://community.projectredcap.org/questions/32001/custom-action-tags-or-module-parameters.html
        if (!class_exists('INTERSECT\RequestedAndRequiredFields\ActionTagHelper')) include_once('classes/ActionTagHelper.php');
        $action_tag_results = ActionTagHelper::getActionTags($tags, $fields, $instruments);
        return $action_tag_results;
    }

    function redcap_survey_page($project_id, $record, $instrument) {

		// Get all annotated fields
        $requestedTag = "@REQUESTED";
        $requiredTag = "@REQUIRED";
		$tags = array($requestedTag, $requiredTag);
        $annotatedFields = $this->getTags($tags, $fields=NULL, $instruments=$instrument);

        // Extract field names and build the new structure, with fieldType and description (or label if unset in the tag)
        $requestedFields = [];
        foreach (array_keys($annotatedFields[$requestedTag]) as $fieldName) {
                $fieldType = REDCap::getFieldType($fieldName);
                $description = trim($annotatedFields[$requestedTag][$fieldName][0], '"');
				if (strlen($description) == 0) $description = $this->getFieldLabel($fieldName);
                $requestedFields[$fieldName] = [
                    'type' => $fieldType,
                    'description' => $this->escape($description)
                ];
        };
        $requiredFields = [];
        foreach (array_keys($annotatedFields[$requiredTag]) as $fieldName) {
                $fieldType = REDCap::getFieldType($fieldName);
                $description = trim($annotatedFields[$requiredTag][$fieldName][0], '"');
				if (strlen($description) == 0) $description = $this->getFieldLabel($fieldName);
                $requiredFields[$fieldName] = [
                    'type' => $fieldType,
                    'description' => $this->escape($description)
                ];
        };

		// Collect project settings
		$settings = $this->getProjectSettings();

		// Language defaults
		$settings['modal-title'] = $settings['modal-title'] ?? $this->tt('modal-title-text');
		$settings['modal-requested-header'] = $settings['modal-requested-header'] ?? $this->tt('modal-requested-header-text');
		$settings['modal-required-header'] = $settings['modal-required-header'] ?? $this->tt('modal-required-header-text');
		$settings['modal-footer-norequired'] = $settings['modal-footer-norequired'] ?? $this->tt('modal-footer-norequired-text');
		$settings['modal-footer-required'] = $settings['modal-footer-required'] ?? $this->tt('modal-footer-required-text');
		$settings['requested-label'] = $settings['requested-label'] ?? $this->tt('requested-label-text');
		$settings['modal-cancel'] = $settings['modal-cancel'] ?? $this->tt('modal-cancel-text');
		$settings['modal-submit'] = $settings['modal-submit'] ?? $this->tt('modal-submit-text');

		// Colour defaults
		$settings['requested-hlcolour'] = $settings['requested-hlcolour'] ?? $this->tt('requested-hlcolour-hex');
		$settings['required-hlcolour'] = $settings['required-hlcolour'] ?? $this->tt('required-hlcolour-hex');
		$settings['requested-label-colour'] = $settings['requested-label-colour'] ?? $this->tt('requested-label-colour-hex');

        echo "<script>
            $('button[name=\"submit-btn-saverecord\"]').attr('onclick','$(this).button(\"disable\");checkReqdFields();return false;');
            var requestedFields = " . json_encode($requestedFields) . ";
            var requiredFields = " . json_encode($requiredFields) . ";
            $.each(requestedFields, function(fieldName, fieldInfo) {
                var fieldRow = $('tr#'+fieldName+'-tr');
                var fieldType = fieldInfo.type;
                var desc = fieldInfo.description;
                if (!fieldRow.length) {
                    delete requestedFields[fieldName];
                }
                var fieldClass = fieldRow.attr('class');
                if (fieldClass && fieldClass.indexOf('@HIDDEN') !== -1) {
                    delete requestedFields[fieldName];
                }
            });
            $.each(requiredFields, function(fieldName, fieldInfo) {
                var fieldRow = $('tr#'+fieldName+'-tr');
                var fieldType = fieldInfo.type;
                var desc = fieldInfo.description;
                if (!fieldRow.length) {
                    delete requiredFields[fieldName];
                }
                var fieldClass = fieldRow.attr('class');
                if (fieldClass && fieldClass.indexOf('@HIDDEN') !== -1) {
                    delete requiredFields[fieldName];
                }
            });
            function fieldIsEmpty(fieldName, fieldType){
                var fieldIsEmpty = false;
                var fieldRow = $('tr#'+fieldName+'-tr');
                if (!fieldRow.is(':visible')) {
                    return fieldIsEmpty;
                }
                switch (fieldType) {
                    case 'text':
                    case 'radio':
                    case 'truefalse':
                    case 'yesno':
                    case 'slider':
                    case 'file':
                    case 'signature':
                        fieldIsEmpty = ($('input[name=\"'+fieldName+'\"]').val()==='');
                        break;
                    case 'notes':
                        fieldIsEmpty = ($('textarea[name=\"'+fieldName+'\"]').val()==='');
                        break;
                    case 'dropdown':
                    case 'sql':
                        fieldIsEmpty = ($('select[name=\"'+fieldName+'\"]').val()==='');
						break;
					case 'checkbox':
						fieldIsEmpty = (!$('tr#' + fieldName+ '-tr').find('input[type=\"checkbox\"]').is(':checked'));
						break;
                }
                return fieldIsEmpty;
            }
            function createEmptyReport(fieldArray){
                var empties = [];
                $.each(fieldArray, function(fieldName, fieldInfo) {
                    var fieldType = fieldInfo.type;
                    var fieldDesc = fieldInfo.description;
                    if (fieldIsEmpty(fieldName, fieldType)) {
                        empties.push(fieldDesc);
                    }
                });
                return empties;
            };
			function addClassToField(fieldName, className){
                var fieldRow = $('tr#'+fieldName+'-tr');
				fieldRow.addClass(className);
			};
            function checkReqdFields(){
				$('#requested-list').empty();
				$('#required-list').empty();
                var emptyRequested = createEmptyReport(requestedFields);
                var emptyRequired = createEmptyReport(requiredFields);
                if (emptyRequested.length + emptyRequired.length == 0){
                    dataEntrySubmit();
                } else {
					if (emptyRequested.length > 0) {
						$('#modal-requested-header').show();
						// Re-enable the modal's submit button
						$('button#confirmSubmit').prop('disabled', false);
						// Create a <ul> element
						var requestedUl = document.createElement('ul');
						// Loop through the descriptions and create <li> elements
						emptyRequested.forEach(function(description) {
							var li = document.createElement('li');
							li.textContent = description;
							requestedUl.appendChild(li);
						});
						document.getElementById('requested-list').appendChild(requestedUl);
					} else {
						$('#modal-requested-header').hide();
					}
					if (emptyRequired.length > 0) {
						$('#modal-required-header').show();
						// Disable modal's submit button
						$('button#confirmSubmit').prop('disabled', true);
						$('#modal-action').text('". $settings['modal-footer-required']  ."');
						// Create a <ul> element
						var requiredUl = document.createElement('ul');
						// Loop through the descriptions and create <li> elements
						emptyRequired.forEach(function(description) {
							var li = document.createElement('li');
							li.textContent = description;
							requiredUl.appendChild(li);
						});
						document.getElementById('required-list').appendChild(requiredUl);
					} else {
						$('#modal-required-header').hide();
					}

					// Show modal
					$('#confirmationModal').modal('show');
					// Handle the OK button click in the modal
					$('button#confirmSubmit').on('click', function() {
						// Hide the modal
						$('#confirmationModal').modal('hide');
						// Call the original function
						dataEntrySubmit();
					});
					// Handle the Cancel button click in the modal
					$('#cancelSubmit, .close').on('click', function() {
						// Re-enable the submit button
						$('#confirmationModal').modal('hide');
						$('button[name=\"submit-btn-saverecord\"]').button('enable')
						$.each(requestedFields, function(fieldName) {
							addClassToField(fieldName, 'em-requested');
						});
						$.each(requiredFields, function(fieldName) {
							addClassToField(fieldName, 'em-required');
						});
					});
					// Re-enable the submit button if the modal is closed without confirmation
					$('#confirmationModal').on('hidden.bs.modal', function () {
						$('button[name=\"submit-btn-saverecord\"]').button('enable')
						$.each(requestedFields, function(fieldName) {
							addClassToField(fieldName, 'em-requested');
						});
						$.each(requiredFields, function(fieldName) {
							addClassToField(fieldName, 'em-required');
						});
					});
                }
            };
		</script>";

		if ($settings['highlight']) {
			echo "<style>
				tr.em-requested .labelrc, tr.em-requested .data {
					background: " . $settings['requested-hlcolour'] . ";
				}
				tr.em-required .labelrc, tr.em-required .data {
					background: " . $settings['required-hlcolour'] . ";
				}
			</style>";
		}

		echo "<div class='modal fade' id='confirmationModal' tabindex='-1' role='dialog' aria-labelledby='confirmationModalLabel' aria-hidden='true'>
				<div class='modal-dialog' role='document'>
					<div class='modal-content'>
						<div class='modal-header'>
							<h5 class='modal-title' id='confirmationModalLabel'>".$settings['modal-title']."</h5>
							<button type='button' class='close' data-dismiss='modal' aria-label='Close'>
								<span aria-hidden='true'>&times;</span>
							</button>
						</div>
						<div class='modal-body'>
							<p id='modal-requested-header'>".$settings['modal-requested-header']."</p>
							<div " . ($settings['highlight'] ? "style='background: " . $settings['requested-hlcolour'] . ";'" : "") . " id='requested-list'></div>
							<p id='modal-required-header'>".$settings['modal-required-header']."</p>
							<div " . ($settings['highlight'] ? "style='background: " . $settings['required-hlcolour'] . ";'" : "") . " id='required-list'></div>
							<p id='modal-action'>".$settings['modal-footer-norequired']."</p>
						</div>
						<div class='modal-footer'>
							<button type='button' class='btn btn-secondary' data-dismiss='modal' id='cancelSubmit'>".$settings['modal-cancel']."</button>
							<button type='button' class='btn btn-primary' id='confirmSubmit'>".$settings['modal-submit']."</button>
						</div>
					</div>
				</div>
			</div>";
		if ($settings['show-requested']) {
			echo "<script>
				var requestedLabelDiv = '<div class=\"requestedlabel\" aria-label=\"response requested\">" . $settings['requested-label'] . "</div>';
				document.addEventListener('DOMContentLoaded', function() {
					$.each(requestedFields, function(fieldName) {
						var fieldRow = $('tr#'+fieldName+'-tr');
						fieldRow.find('label#label-' + fieldName + ' div[data-mlm-field=\"' + fieldName + '\"]').append(requestedLabelDiv);
					});
				});
				</script>
				<style>
					.requestedlabel {
						font-size: 12px;
						color: " . $settings['requested-label-colour'] . ";
						font-weight: normal;
					}
				</style>";
		}
		if ($settings['disable-greenhl']) {
		echo "<script>
				doGreenHighlight = function() {};
			</script>";
		}
    }
}
