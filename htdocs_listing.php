<!DOCTYPE html>
<html>
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link href="/themes/v2/assets/css/bootstrap.min.css" rel="stylesheet">
    <script src="/themes/v2/assets/js/bootstrap.min.js"></script>
    <script src="/themes/v2/assets/js/jquery-3.5.1.min.js"></script>
    <link href='/themes/v2/assets/css/font-awesome.min.css' rel='stylesheet'/>
	<link rel="shortcut icon" href="../favicon.ico" type="image/ico" />
    <title>Localhost | Welcome XAMPP</title>
    <base target="_blank"/>
    <style>
	.xampp_head1
	{
		
	border-top: 1px solid #cfcfcf;
	border-bottom: 1px solid #cfcfcf;
	background-color: #2a5d84;
	color: #fff;

	}
	.project_list
	{
		max-height: 500px;
height: 400px;
overflow-y: auto;
	}
        .single {
            padding: 30px 15px;
            margin-top: 40px;
            background: #c9c9c933;
            border: 1px solid #f0f0f0;
        }

        .single h3.side-title {
            margin: 0;
            margin-bottom: 10px;
            padding: 0;
            font-size: 20px;
            color: #333;
            text-transform: uppercase;
        }

        .single h3.side-title:after {
            content: '';
            width: 60px;
            height: 1px;
            background: #ff173c;
            display: block;
            margin-top: 6px;
        }

        .single ul {
            margin-bottom: 0;
        }

        .single li a {
            color: #666;
            font-size: 14px;
            border-bottom: 1px solid #f0f0f0;
            line-height: 30px;
            display: block;
            text-decoration: none;
        }

        .single li a:hover {
            color: #2a5d84;
        }

        .single li:last-child a {
            border-bottom: 0;
        }
    </style>
</head>
<body>
<div class="container single category">
    <div class="row">
        <h1 class="xampp_head1"><img src="/dashboard/images/xampp-logo.svg" style="height: 1.3em;margin: 15px;"> XAMPP <span>Apache + MariaDB + PHP + Perl</span></h1>
        <div class="col-sm-4">

            <hr/>
            <h4><i class="fa fa-list-ol" aria-hidden="true"></i>
                Project Lists</h4>
				<hr/>
            <?php
            $htdocs = opendir(".");
            $list = '';

            while ($project = readdir($htdocs)) {
                if (is_dir($project) && ($project != "..") && ($project != "."))
					//if($project == "dashboard" || $project == "dashboard")
                    $list .= '
<li><a href="../' . $project . '"><i class="fa fa-folder-open-o" aria-hidden="true" style="color: #fbd246;"></i> ' . $project . '</a></li>';
            }
            closedir($htdocs);

            if (!isset($list))
                $list = "No list";
            ?>

            <div class="project_list">
                <ol class="list-unstyled">
                    <?php echo $list ?>
                </ol>
            </div>
        </div>
        <div class="col-sm-4">

            <hr/>
            <h4><i class="fa fa-wrench" aria-hidden="true"></i>
                Tools</h4>
<hr/>

            <div>
                <ol class="list-unstyled">
                    <li><a href="../dashboard/phpinfo.php">PHPInfo</a></li>
                    <li><a href="../phpmyadmin">phpMyAdmin</a></li>

                </ol>
            </div>
        </div>
        <div class="col-sm-4">

            <hr/>
            <h4><i class="fa fa-info-circle" aria-hidden="true"></i> Important Links</h4>
<hr/>

            <div>
                <ol class="list-unstyled">
                    <li><a href="../dashboard">Dashboard</a></li>
                    <li><a href="../applications.html">Applications</a></li>
                    <li><a href="../faq.html">FAQs</a></li>
                    <li><a href="../howto.html">HOW-TO Guides</a></li>

                </ol>
            </div>
        </div>
    </div>
    <!-- Footer -->
    <footer class="page-footer font-small blue">

        <!-- Copyright -->
        <div class="footer-copyright text-center py-3">© 2019 Copyright:
            | Kiran RS
        </div>
        <!-- Copyright -->

    </footer>
    <!-- Footer -->
</div>


</body>
</html>
