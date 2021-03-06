 <?php

require  'Slim/Slim.php';
require  'Redbeanphp/rb.php';
require  'Token/Token.php';

require_once 'conf.php';
\Slim\Slim::registerAutoloader();

R::setup('mysql:host='.$conf['host'].';dbname='.$conf['dbname'],$conf['dbuser'],$conf['dbpassword']);
R::freeze(true);


$groupMap = array(
	"Д" => "DID",
	"Др." => "OTHR",
	"КП" => "CSP" /*ComputerScience - Practicum*/,
	"М" => "MAT",
	"ОКН" => "CSF" /*CS Fundamentals*/,
	"ПМ" => "APM" /*APPLIED MATH*/,
	"С" => "SEM" /*Seminars*/,
	"Ст" => "STAT" /*Statistics*/,
	"Х" => "HUM" /*Humanitarian*/,
	"ЯКН" => "CSC" /*CS Core*/,
	"И" => "INF" /*informatics*/,
	"ПМ / Ст" => array("APM", "STAT"), /*wtf fmi*/
	"ПМ/Ст" => array("APM", "STAT"), /*wtf fmi x2*/
	"ОКН/Ст" => array("CSF", "STAT") /*wtf fmi x3*/
);

$app = new \Slim\Slim();
$tkn_ = new ApiToken();

function generateExceptionError($app,$exception) {
	$error['error']=$exception->getMessage();
	$app->response()->header('Content-Type', 'application/json');
	$app->response()->status($exception->getCode());
	echo json_encode($error  , JSON_UNESCAPED_UNICODE );
};

function generateCustomError($app,$code,$message) {
	$error['error']=$message;
	$app->response()->header('Content-Type', 'application/json');
	$app->response()->status($code);
	echo json_encode($error  , JSON_UNESCAPED_UNICODE );
};

$app->get('/register_token', function () use ($app,$tkn_) {
		$token='';
		$ip_address = "";
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARTDED_FOR'] != '') {
			$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} 
		else {
			$ip_address = $_SERVER['REMOTE_ADDR'];
		}
		$token=$tkn_->generateToken($token,$ip_address);
		if (isset($token)) {
			$generatedToken['token']=$token;
			$app->response()->header('Content-Type', 'application/json');
			echo json_encode($generatedToken,JSON_UNESCAPED_UNICODE);
		}
		else {
			$error['error'] = 'No token was generated';
			$app->response()->header('Content-Type', 'application/json');
			echo json_encode($error,JSON_UNESCAPED_UNICODE);
		}
});

$app->get('/:token/programs', function ($token) use ($app,$tkn_) {
	if ($tkn_->checkToken($token))
	  {
		try {
			$programs=R::getAll('SELECT * FROM bachelor_programmes');
			if ($programs) {
				$app->response()->header('Content-Type', 'application/json');
				echo json_encode($programs,JSON_UNESCAPED_UNICODE);
			}
			else {
				throw new Exception('Nothing was found',400);
			}
		}
		catch (Exception $e) {
				 generateExceptionError($app,$e);
		}
	  }
	else{
	    generateCustomError($app,400,"Invalid Token");
	  }
});

$app->get('/:token/program/:id', function ($token,$id) use ($app,$tkn_) {
	if ($tkn_->checkToken($token)) {
		if ( preg_match('/^\d{1,}$/', $id) ) {
			try {
				$escaped_id = mysql_real_escape_string($id);
				$program = R::getRow("SELECT * FROM bachelor_programmes WHERE programme_id = $escaped_id");
				if ($program) {
					$app->response()->header('Content-Type', 'application/json');
					echo json_encode($program  , JSON_UNESCAPED_UNICODE );
				}
				else {
					throw new Exception('Nothing was found',400);
				}
			}
			catch (Exception $e) {
				generateExceptionError($app,$e);
			}
		}
		else {
			generateCustomError($app,400,"ID is missing");
		}
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

$app->get('/:token/teachers', function ($token) use ($app,$tkn_) {
	if ($tkn_->checkToken($token)) {
		try {
			$teachers = R::getAll("SELECT * FROM teachers");
			if ($teachers) {
				$app->response()->header('Content-Type', 'application/json');
				echo json_encode($teachers  , JSON_UNESCAPED_UNICODE );
			}
			else {
				throw new Exception('Nothing was found',400);
			}
		}
		catch (Exception $e) {
			generateExceptionError($app,$e);
		}
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

$app->get('/:token/teachers/department/:department', function ($token,$department) use ($app,$tkn_) {
	if ($tkn_->checkToken($token)) {
		try {
			$escaped_department = mysql_real_escape_string($department);
			$teachers = R::getAll("SELECT * FROM teachers WHERE department='$escaped_department'");
			if ($teachers) {
				$app->response()->header('Content-Type', 'application/json');
				echo json_encode($teachers  , JSON_UNESCAPED_UNICODE );
			}
			else {
				throw new Exception('Nothing was found',400);
			}
		}
		catch (Exception $e) {
			generateExceptionError($app,$e);
		}
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

$app->get('/:token/teachers/position/:position', function ($token,$position) use ($app,$tkn_) {
	if ($tkn_->checkToken($token)) {
		try {
			$escaped_position = mysql_real_escape_string($position);
			$teachers = R::getAll("SELECT * FROM teachers WHERE teacher_position='$escaped_position'");
			if ($teachers) {
				$app->response()->header('Content-Type', 'application/json');
				echo json_encode($teachers  , JSON_UNESCAPED_UNICODE );
			}
			else {
				throw new Exception('Nothing was found',400);
			}
		}
		catch (Exception $e) {
			generateExceptionError($app,$e);
		}
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

$app->get('/:token/teachers/course/:course_id', function ($token,$course_id) use ($app,$tkn_) {
	if ($tkn_->checkToken($token)) {
		try {
			$escaped_course_id = mysql_real_escape_string($course_id);
			$teachers = R::getAll("SELECT * FROM teachers LEFT JOIN courses_teachers on teachers.teacher_id=courses_teachers.teacher_id WHERE courses_teachers.course_id='$escaped_course_id'");
			if ($teachers) {
				$app->response()->header('Content-Type', 'application/json');
				echo json_encode($teachers  , JSON_UNESCAPED_UNICODE );
			}
			else {
				throw new Exception('Nothing was found',400);
			}
		}
		catch (Exception $e) {
			generateExceptionError($app,$e);
		}
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

$app->get('/:token/teacher/:id', function ($token,$id) use ($app,$tkn_) {
	if ($tkn_->checkToken($token)) {
		if ( preg_match('/^\d{1,}$/', $id) ) {
			try {
				$escaped_id = mysql_real_escape_string($id);
				$teacher = R::getRow("SELECT * FROM teachers WHERE teacher_id = $escaped_id");
				if ($teacher) {
					$app->response()->header('Content-Type', 'application/json');
					echo json_encode($teacher  , JSON_UNESCAPED_UNICODE );
				}
				else {
					throw new Exception('Nothing was found',400);
				}
			}
			catch (Exception $e) {
				generateExceptionError($app,$e);
			}
		}
		else {
			generateCustomError($app,400,"ID is missing");
		}
			
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

$app->get('/:token/semesters', function ($token) use ($app,$tkn_) {
	if ($tkn_->checkToken($token)) {
		try {
			$smesters = R::getAll("SELECT * FROM semesters");
			if ($smesters) {
				$app->response()->header('Content-Type', 'application/json');
				echo json_encode($smesters  , JSON_UNESCAPED_UNICODE );
			}
			else {
				throw new Exception('Nothing was found',400);
			}
		}
		catch (Exception $e) {
			generateExceptionError($app,$e);
		}
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

$app->get('/:token/semester_filter/:season(/:start_date)', function ($token,$season="",$start_date=0) use ($app,$tkn_) {
	if ($tkn_->checkToken($token)) {
        $where_clause="";
        if ( !empty($season) ) {
            $escaped_season=mysql_real_escape_string($season);
            $where_clause.=" AND semester_season='$escaped_season'";
        }
        if ( $start_date>0 ) {
            $escaped_start_date=mysql_real_escape_string($start_date);
            $where_clause.=" AND season_start_year=$escaped_start_date";
        }

        $where_clause = preg_replace('/^ AND/', '', $where_clause);
        if ( strlen($where_clause)>0 ) {
            $where_clause = " WHERE ".$where_clause;
            try {
                $escaped_season = mysql_real_escape_string($season);
                $semester = R::getAll("SELECT * FROM semesters $where_clause");
                if ($semester) {
                    $app->response()->header('Content-Type', 'application/json');
                    echo json_encode($semester  , JSON_UNESCAPED_UNICODE );
                }
                else {
                    throw new Exception('Nothing was found',400);
                }
            }
            catch (Exception $e) {
                generateExceptionError($app,$e);
            }
        }
        else {
            generateCustomError($app,400,"Please specify.");
        }
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

$app->get('/:token/semesters/season/:season', function ($token,$season) use ($app,$tkn_) {
	if ($tkn_->checkToken($token)) {
		try {
			$escaped_season = mysql_real_escape_string($season);
			$smesters = R::getAll("SELECT * FROM semesters WHERE semester_season LIKE '$escaped_season'  ");
			if ($smesters) {
				$app->response()->header('Content-Type', 'application/json');
				echo json_encode($smesters  , JSON_UNESCAPED_UNICODE );
			}
			else {
				throw new Exception('Nothing was found',400);
			}
		}
		catch (Exception $e) {
			generateExceptionError($app,$e);
		}
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

$app->get('/:token/semesters/start/:year_start', function ($token,$year_start) use ($app,$tkn_) {
	if ($tkn_->checkToken($token)) {
		try {
			$escaped_year_start = mysql_real_escape_string($year_start);
			$smesters = R::getAll("SELECT * FROM semesters WHERE season_start_year= $escaped_year_start");
			if ($smesters) {
				$app->response()->header('Content-Type', 'application/json');
				echo json_encode($smesters  , JSON_UNESCAPED_UNICODE );
			}
			else {
				throw new Exception('Nothing was found',400);
			}
		}
		catch (Exception $e) {
			generateExceptionError($app,$e);
		}
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

$app->get('/:token/semesters/end/:year_end', function ($token,$year_end) use ($app,$tkn_) {
	if ($tkn_->checkToken($token)) {
		try {
			$escaped_year_end = mysql_real_escape_string($year_end);
			$smesters = R::getAll("SELECT * FROM semesters WHERE season_end_year= $escaped_year_end");
			if ($smesters) {
				$app->response()->header('Content-Type', 'application/json');
				echo json_encode($smesters  , JSON_UNESCAPED_UNICODE );
			}
			else {
				throw new Exception('Nothing was found',400);
			}
		}
		catch (Exception $e) {
			generateExceptionError($app,$e);
		}
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

$app->get('/:token/semester/:id', function ($token,$id) use ($app,$tkn_) {
	if ($tkn_->checkToken($token)) {
		if ( preg_match('/^\d{1,}$/', $id) ) {
			try {
				$escaped_id = mysql_real_escape_string($id);
				$smester = R::getRow("SELECT * FROM semesters WHERE id = $escaped_id");
				if ($smester) {
					$app->response()->header('Content-Type', 'application/json');
					echo json_encode($smester  , JSON_UNESCAPED_UNICODE );
				}
				else {
					throw new Exception('Nothing was found',400);
				}
			}
			catch (Exception $e) {
				generateExceptionError($app,$e);
			}
		}
		else {
			generateCustomError($app,400,"ID is missing");
		}
			
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

$app->get('/:token/students', function ($token) use ($app,$tkn_) {
	if ($tkn_->checkToken($token)) {
		try {
			$students = R::getAll("SELECT * FROM students");
			if ($students) {
				$app->response()->header('Content-Type', 'application/json');
				echo json_encode($students  , JSON_UNESCAPED_UNICODE );
			}
			else {
				throw new Exception('Nothing was found',400);
			}
		}
		catch (Exception $e) {
			generateExceptionError($app,$e);
		}
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

$app->get('/:token/students/fn/:fn', function ($token,$fn) use ($app,$tkn_) {
	if ($tkn_->checkToken($token)) {
		if ( preg_match('/^\d{4,}$/', $fn) ) {
			try {
				$escaped_fn = mysql_real_escape_string($fn);
				$students = R::getAll("SELECT * FROM students WHERE fn=$escaped_fn");
				if ($students) {
					$app->response()->header('Content-Type', 'application/json');
					echo json_encode($students  , JSON_UNESCAPED_UNICODE );
				}
				else {
					throw new Exception('Nothing was found',400);
				}
			}
			catch (Exception $e) {
				generateExceptionError($app,$e);
			}
		}
		else {
			generateCustomError($app,400,"FN is missing");
		}
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

$app->get('/:token/students_filter/:course_id(/:year)', function ($token,$course_id=0,$year=0) use ($app,$tkn_) {
	if ($tkn_->checkToken($token)) {
		$where_clause="";
		if ( $course_id>0 ) {
			$escaped_course_id=mysql_real_escape_string($course_id);
			$where_clause.=" AND course=$escaped_course_id";
		}
		if ( $year>0 ) {
			$escaped_year=mysql_real_escape_string($year);
			$where_clause.=" AND year=$escaped_year";
		}
		
		$where_clause = preg_replace('/^ AND/', '', $where_clause);
		
		if ( strlen($where_clause)>0 ) {
			$where_clause = ' WHERE '.$where_clause;
			try {
				$students = R::getAll("SELECT * FROM students $where_clause");
				if ($students) {
					$app->response()->header('Content-Type', 'application/json');
					echo json_encode($students  , JSON_UNESCAPED_UNICODE );
				}
				else {
					throw new Exception('Nothing was found',400);
				}
			}
			catch (Exception $e) {
				generateExceptionError($app,$e);
			}
		}
		else {
			generateCustomError($app,400,"Please specify.");
		}
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

$app->get('/:token/student/:id', function ($token,$id) use ($app,$tkn_) {
	if ($tkn_->checkToken($token)) {
		if ( preg_match('/^\d{1,}$/', $id) ) {
			try {
				$escaped_id = mysql_real_escape_string($id);
				$students = R::getAll("SELECT * FROM students WHERE id=$escaped_id");
				if ($students) {
					$app->response()->header('Content-Type', 'application/json');
					echo json_encode($students  , JSON_UNESCAPED_UNICODE );
				}
				else {
					throw new Exception('Nothing was found',400);
				}
			}
			catch (Exception $e) {
				generateExceptionError($app,$e);
			}
		}
		else {
			generateCustomError($app,400,"ID is missing");
		}
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

$app->get('/:token/courses', function ($token) use ($app,$tkn_) {
	if ($tkn_->checkToken($token)) {
		try {
			$courses = R::getAll("SELECT courses.course_id, courses.course_name, courses.group, courses.credits, courses.semester, bachelor_programmes.programme_name, courses.year FROM courses LEFT JOIN bachelor_programmes on courses.programme_id=bachelor_programmes.programme_id");
			if ($courses) {
				$i=0;
				while ($i<count($courses)) {
					$course_id=$courses[$i]['course_id'];
					$teachers = R::getAll("SELECT courses_teachers.teacher_id,teachers.teacher_name FROM courses_teachers LEFT JOIN teachers on courses_teachers.teacher_id=teachers.teacher_id WHERE courses_teachers.course_id=$course_id");
					if ( $teachers ) {
						$courses[$i]['teachers']=$teachers;
					}
					$i++;
				}
				$app->response()->header('Content-Type', 'application/json');
				echo json_encode($courses  , JSON_UNESCAPED_UNICODE );
			} 
			else {
				throw new Exception('Nothing was found',400);
			}
		}
		catch (Exception $e) {
				generateExceptionError($app,$e);
		}
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

$app->get('/:token/course/:id', function ($token,$id) use ($app,$tkn_) {
	if ($tkn_->checkToken($token)) {
		if ( preg_match('/^\d{1,}$/', $id) ) {
			try {
				$escaped_id = mysql_real_escape_string($id);
				$program = R::getRow("SELECT courses.course_id, courses.course_name, courses.group, courses.credits, courses.semester, bachelor_programmes.programme_name, courses.year FROM courses LEFT JOIN bachelor_programmes on courses.programme_id=bachelor_programmes.programme_id WHERE courses.course_id = $escaped_id");
				if ($program) {
					$teachers = R::getAll("SELECT courses_teachers.teacher_id,teachers.teacher_name FROM courses_teachers LEFT JOIN teachers on courses_teachers.teacher_id=teachers.teacher_id WHERE courses_teachers.course_id=$escaped_id");
					if ( $teachers ) {
						$program['teachers']=$teachers;
					}
					$app->response()->header('Content-Type', 'application/json');
					echo json_encode($program  , JSON_UNESCAPED_UNICODE );
				}
				else {
					throw new Exception('Nothing was found',400);
				}
			}
			catch (Exception $e) {
				generateExceptionError($app,$e);
			}
		}
		else {
			generateCustomError($app,400,"ID is missing");
		}
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

$app->get('/:token/courses/year/:year', function ($token,$year) use ($app,$tkn_) {
	if ($tkn_->checkToken($token)) {
		if ( preg_match('/^[0-4]$/', $year) ) {
			try {
				$escaped_year=mysql_real_escape_string($year);
				$courses = R::getAll("SELECT courses.course_id, courses.course_name, courses.group, courses.credits, courses.semester, bachelor_programmes.programme_name, courses.year FROM courses LEFT JOIN bachelor_programmes on courses.programme_id=bachelor_programmes.programme_id WHERE courses.year=$escaped_year");
				if ($courses) {
					$i=0;
					while ($i<count($courses)) {
						$course_id=$courses[$i]['course_id'];
						$teachers = R::getAll("SELECT courses_teachers.teacher_id,teachers.teacher_name FROM courses_teachers LEFT JOIN teachers on courses_teachers.teacher_id=teachers.teacher_id WHERE courses_teachers.course_id=$course_id");
						if ( $teachers ) {
							$courses[$i]['teachers']=$teachers;
						}
						$i++;
					}
					$app->response()->header('Content-Type', 'application/json');
					echo json_encode($courses  , JSON_UNESCAPED_UNICODE );
				}
				else {
					throw new Exception('Nothing was found',400);
				}
			}
			catch (Exception $e) {
					  generateExceptionError($app,$e);
			}
		}
		else {
			generateCustomError($app,400,"Should be a number from 0 to 4");
		}
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

$app->get('/:token/courses/semester/:semester', function ($token,$semester) use ($app,$tkn_) {
	if ($tkn_->checkToken($token)) {
		if ( preg_match('/^\d{1,}$/', $semester) ) {
			try {
				$escaped_semester=mysql_real_escape_string($semester);
				$courses = R::getAll("SELECT courses.course_id, courses.course_name, courses.group, courses.credits, courses.semester, bachelor_programmes.programme_name, courses.year FROM courses LEFT JOIN bachelor_programmes on courses.programme_id=bachelor_programmes.programme_id WHERE courses.semester=$escaped_semester");
				if ($courses) {
					$i=0;
					while ($i<count($courses)) {
						$course_id=$courses[$i]['course_id'];
						$teachers = R::getAll("SELECT courses_teachers.teacher_id,teachers.teacher_name FROM courses_teachers LEFT JOIN teachers on courses_teachers.teacher_id=teachers.teacher_id WHERE courses_teachers.course_id=$course_id");
						if ( $teachers ) {
							$courses[$i]['teachers']=$teachers;
						}
						$i++;
					}
					$app->response()->header('Content-Type', 'application/json');
					echo json_encode($courses  , JSON_UNESCAPED_UNICODE );
				}
				else {
					throw new Exception('Nothing was found',400);
				}
			}
			catch (Exception $e) {
					  generateExceptionError($app,$e);
			}
		}
		else {
			generateCustomError($app,400,"Not in the correct format");
		}
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

$app->get('/:token/courses/credits/:credits(/:program)', function ($token,$credits,$program=0) use ($app,$groupMap,$tkn_) {
	if ($tkn_->checkToken($token)) {
		$courses="";
		$program_where="";
		if ( $program>0 ) {
			$escaped_program = mysql_real_escape_string($program);
			$program_where=" AND courses.programme_id=$escaped_program";
		}
		if ( preg_match('/^>\d{1,}\.?\d*$/', $credits) ) {
			$credits=substr($credits, 1);
			$escaped_credits=mysql_real_escape_string($credits);
			$courses = R::getAll("SELECT courses.course_id, courses.course_name, courses.group, courses.credits, courses.semester, bachelor_programmes.programme_name, courses.year FROM courses LEFT JOIN bachelor_programmes on courses.programme_id=bachelor_programmes.programme_id WHERE courses.credits>$escaped_credits$program_where");
		}
		else if ( preg_match('/^<\d{1,}\.?\d*$/', $credits) ) {
			$credits=substr($credits, 1);
			$escaped_credits=mysql_real_escape_string($credits);
			$courses = R::getAll("SELECT courses.course_id, courses.course_name, courses.group, courses.credits, courses.semester, bachelor_programmes.programme_name, courses.year FROM courses LEFT JOIN bachelor_programmes on courses.programme_id=bachelor_programmes.programme_id WHERE courses.credits<$escaped_credits$program_where");
		}
		else if ( preg_match('/^\d{1,}\.?\d*$/', $credits) ) {
			$escaped_credits=mysql_real_escape_string($credits);
			$courses = R::getAll("SELECT courses.course_id, courses.course_name, courses.group, courses.credits, courses.semester, bachelor_programmes.programme_name, courses.year FROM courses LEFT JOIN bachelor_programmes on courses.programme_id=bachelor_programmes.programme_id WHERE courses.credits=$escaped_credits$program_where");
		}
		else {
			generateCustomError($app,400,"Not in the correct format");
			return;
		}
		
		if ($courses) {
			$i=0;
			while ($i<count($courses)) {
				$course_id=$courses[$i]['course_id'];
				$teachers = R::getAll("SELECT courses_teachers.teacher_id,teachers.teacher_name FROM courses_teachers LEFT JOIN teachers on courses_teachers.teacher_id=teachers.teacher_id WHERE courses_teachers.course_id=$course_id");
				if ( $teachers ) {
					$courses[$i]['teachers']=$teachers;
				}
				$i++;
			}
			$app->response()->header('Content-Type', 'application/json');
			echo json_encode($courses  , JSON_UNESCAPED_UNICODE );
		}
		else {
			generateCustomError($app,400,"Nothing was found");
		}
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

$app->get('/:token/courses/group/:group', function ($token,$group) use ($app,$groupMap,$tkn_) {
	if ($tkn_->checkToken($token)) {
		if ( array_key_exists($group,$groupMap) ) {
			try {
				$escaped_group=mysql_real_escape_string($groupMap[$group]);
				$courses = R::getAll("SELECT courses.course_id, courses.course_name, courses.group, courses.credits, courses.semester, bachelor_programmes.programme_name, courses.year FROM courses LEFT JOIN bachelor_programmes on courses.programme_id=bachelor_programmes.programme_id WHERE courses.group ='$escaped_group'");
				if ($courses) {
					$i=0;
					while ($i<count($courses)) {
						$course_id=$courses[$i]['course_id'];
						$teachers = R::getAll("SELECT courses_teachers.teacher_id,teachers.teacher_name FROM courses_teachers LEFT JOIN teachers on courses_teachers.teacher_id=teachers.teacher_id WHERE courses_teachers.course_id=$course_id");
						if ( $teachers ) {
							$courses[$i]['teachers']=$teachers;
						}
						$i++;
					}
					$app->response()->header('Content-Type', 'application/json');
					echo json_encode($courses  , JSON_UNESCAPED_UNICODE );
				}
				else {
					throw new Exception('Nothing was found',400);
				}
			}
			catch (Exception $e) {
					  generateExceptionError($app,$e);
			}
		}
		else {
			generateCustomError($app,400,"Not in the correct format");
		}
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

$app->get('/:token/courses/program/:year(/:program_id(/:semester))', function ($token,$year=0,$program_id=0,$semester_id=0) use ($app,$groupMap,$tkn_) {
	if ($tkn_->checkToken($token)) {
		$where_clause="";
		if ( $year>0 ) {
			$escaped_year=mysql_real_escape_string($year);
			$where_clause.=" AND courses.year=$escaped_year";
		}
		if ( $program_id>0 ) {
			$escaped_program_id=mysql_real_escape_string($program_id);
			$where_clause.=" AND courses.programme_id=$escaped_program_id";
		}
		if ( $semester_id>0 ) {
			$escaped_semester_id=mysql_real_escape_string($semester_id);
			$where_clause.=" AND courses.semester=$escaped_semester_id";
		}
		
		$where_clause = preg_replace('/^ AND/', '', $where_clause);
		
		if ( strlen($where_clause)>0 ) {
			$where_clause = ' WHERE '.$where_clause;
			try {
				$program = R::getAll("SELECT courses.course_id, courses.course_name, courses.group, courses.credits, courses.semester, bachelor_programmes.programme_name, courses.year FROM courses LEFT JOIN bachelor_programmes on courses.programme_id=bachelor_programmes.programme_id $where_clause");
				if ($program) {
					$i=0;
					while ($i<count($program)) {
						$course_id=$program[$i]['course_id'];
						$teachers = R::getAll("SELECT courses_teachers.teacher_id,teachers.teacher_name FROM courses_teachers LEFT JOIN teachers on courses_teachers.teacher_id=teachers.teacher_id WHERE courses_teachers.course_id=$course_id");
						if ( $teachers ) {
							$program[$i]['teachers']=$teachers;
						}
						$i++;
					}
					$app->response()->header('Content-Type', 'application/json');
					echo json_encode($program  , JSON_UNESCAPED_UNICODE );
				}
				else {
					throw new Exception('Nothing was found',400);
				}
			}
			catch (Exception $e) {
					  generateExceptionError($app,$e);
			}
		}
		else {
			generateCustomError($app,400,"Please specify.");
		}
	}
	else {
		generateCustomError($app,400,"Invalid Token");
	}
});

R::close();
$app->run();