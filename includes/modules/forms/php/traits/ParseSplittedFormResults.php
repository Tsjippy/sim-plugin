<?php
namespace SIM\FORMS;

trait ParseSplittedFormResults{
	 /**
	 * This function creates seperated entries from entries with an splitted value
	 */
	protected function processSplittedData(){
		if(empty($this->formData->settings['split'])){
			return;
		}

		$fieldMainName	= $this->formData->settings['split'];
		$this->splittedSubmissionData   = [];
		//loop over all submissions
		foreach($this->submissionData as $key=>$entry){
			// loop over all entries of the split key
			foreach($entry->formresults[$fieldMainName] as $subKey=>$array){
				// Should always be an array
				if(!is_array($array)){
					continue;
				}

				// Check if it has data
				$hasData	= false;
				foreach($array as $value){
					if(!empty($value)){
						$hasData = true;
						break;
					}
				}

				if(!$hasData){
					continue;
				}

				// If it has data add as a seperate item to the submission data
				$newSubmission	= clone $entry;
				// Mark this submission as archived if needed
				if(isset($array['archived']) && $array['archived']){
					if($this->showArchived){
						$newSubmission->archived	= true;
						unset($array['archived']);
					}else{
						continue;
					}
				}

				// Add the array to the formresults array
				$newSubmission->formresults = array_merge($entry->formresults, $array);

				// remove the index value from the copy
				unset($newSubmission->formresults[$fieldMainName]);

				// Add the subkey
				$newSubmission->sub_id	= $subKey;

				// Copy the entry
				$this->splittedSubmissionData[]	= $newSubmission;
			}

			// remove the original entry
			//unset($this->submissionData[$key]);
		}
	}
}
