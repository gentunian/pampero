<?php

	function do_groups($args) {
		$content = file_get_contents(PACKAGES_GROUP_FILE);
		$data = json_decode($content, true);

		foreach ($data as $groupName => $values) {
			$result[$groupName] = new Group($values);
		}
		return $result;
	}

	function do_groups_output($result, $args) {
		$opts = new Options(
			$args,
			array("group" => NULL, "output" => Utils::getDefaultOutput())
			);
		$groupName = $opts->getOption("group");
		$outputType = $opts->getOption("output");
		$output = "";
		if ($groupName != NULL) {
			if (array_key_exists($groupName, $result)) {
				$group = $result[$groupName];
				if ($outputType == "jsonplain") {
					$output = $group->toJSON();
				} else if ($outputType == "console") {
					$output = $group->toString();
				}
			}
		} else {
			foreach ($result as $groupName => $group) {
				if ($outputType == "jsonplain") {
					$output .= $group->toJSON() . ",";
				} else if ($outputType == "console") {
					$output .= $group->toString();
				}
			}
			if ($outputType == "jsonplain") {
				$output = '[' . rtrim($output, ",") . ']';
			}
		}
		return $output;

	}

	class Group {
		private $data;

		function __construct($data) {
			$this->data = $data;
		}

		function getName() {
			return $this->data['name'];
		}

		function getDescription() {
			return $this->data['description'];
		}

		function getPackages() {
			return $this->data['packages'];
		}

		function toString() {
			$output = $this->getName() . ":\n";
			$output .=  str_repeat("-", strlen($this->getName())) . "\n";
			$output .= "Description: \n\t" . $this->getDescription() . "\n";
			$output .= "Packages: \n";
			foreach ($this->getPackages() as $key => $value) {
				$output .= "\t${key}. ${value}\n";
			}
			return $output;
		}

		function toJSON() {
			$output = "{";
			$output .= "name: " . $this->getName() . ",";
			$output .= "description: " . $this->getDescription() . ",";
			$output .= "packages: [";
			foreach ($this->getPackages() as $key => $value) {
				$output .= "${value},";
			}
			$output = rtrim($output, ",");
			$output .= "]";
			$output .= "}";
			return json_encode($this->data);
		}
	}
?>