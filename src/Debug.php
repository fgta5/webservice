<?php namespace Fgta5\Webservice;

class Debug {


	public static function die($obj, $bt=null) {
		$bt = $bt==null ? debug_backtrace() : $bt;
		self::screen($obj, $bt);
		die();
	}

	public static function screen($obj, $bt=null) {
		$bt = $bt==null ? debug_backtrace() : $bt;
		$caller = array_shift($bt);

		echo "<div><b>Debug Screen</b></div>";
		echo "<pre>";
		echo "<b>file</b> " . $caller['file'] , "\r\n";
		echo "<b>line</b> " . $caller['line'] , "\r\n";
		echo "<b>output</b>:\r\n\r\n";

		if (is_array($obj) || is_object($obj)) {
			print_r($obj);
		} else {
			echo $obj;
		}
		echo "</pre>";
		echo "<br>";
		echo "<b>BackTrace:</b><br>";
		echo "<table border=1>";
		echo "<tr><td><b>File</b></td><td><b>Line</b></td><td><b>Function</b></td></tr>";
		foreach ($bt as $b) {
			$file = $b['file'];
			$line = $b['line'];
			$func = $b['function'];
			echo "<tr>";
			echo "<td>$file</td><td>$line</td><td>$func</td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<br>";
		echo "<b>Router</b>";
		echo "<table border=1>";
		echo "<tr><td>UsedRoute</td><td>";
		echo Router::getUsedRoute();
		echo "</td></tr>";
		echo "<tr><td>RequestedParameter</td><td>";
		echo Router::getRequestedParameter();
		echo "</td></tr>";
		echo "<tr><td>RequestedParameter</td><td><pre>";
		print_r(Router::getRouteData());
		echo "</pre></td></tr>";
		echo "</table>";
	}
}