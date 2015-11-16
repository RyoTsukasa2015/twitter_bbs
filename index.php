<?php
session_start();
require('dbconnect.php');
//ログインしている方。ログイン時に記録しているidが存在しているのかどうか＋ログインしてから１時間以内か
//$SESSION['time']とは、現在のある時間から、時間数3600秒の時間が経っているかどうかをチェック。
//require()とは、データベースの接続処理の流れを省くことができるマーク。
if(isset($_SESSION['id']) && $_SESSION['time'] +3600 > time()){

	$_SESSION['time'] = time();
	//今現在ログインしている人のユーザー情報を取得
	//mysqli_query...SQL文をDBで実行する関数
	//mysqli_error...DB処理でエラーが発生した場合、エラーメッセージ返す
	//die...メッセージを表示して処理を終了する。
	//mysqli_fetch_assoc...実行結果をフェッチする関数
	//フェッチ...実行結果から１行分のデータを取りだして、カーソルを下に移動する。
	//mysqli_real_escape_stringはサニタイズしてくれる関数

	$sq1 = sprintf('SELECT * FROM members WHERE id=%d',
		mysqli_real_escape_string($db,$_SESSION['id'])
		);

	$record = mysqli_query($db,$sq1) or die(mysqli_error($db));
	$member = mysqli_fetch_assoc($record);
} else{
	//ログインしていない

	//var_dump($_SESSION['id']);
	//var_dump($_SESSION['time']);
	header('Location: login.php');
	exit();
}
	//投稿を記録する
	//post送信されているのであれば、
	if(!empty($_POST)){
	//メッセージがから出なかった場合は
		if($_POST['message'] !=''){
		//%d...整数型のデータを置換する文字
		//%s...文字型のデータを置換する文字
		//SQL文では数字をダブルクォーテーションで囲まない（文字は囲む）
		$sq1 = sprintf('INSERT INTO posts SET member_id=%d, message="%s", reply_post_id=%d, created=NOW()',
			mysqli_real_escape_string($db,$member['id']),
			mysqli_real_escape_string($db,$_POST['message']),
			mysqli_real_escape_string($db,$_POST['reply_post_id'])
			);
		mysqli_query($db,$sq1)or die(mysqli_error($db));
		header('Location: index.php');
	 	exit();
		}
}	
	//投稿を取得する
	//テーブル結合という。２つのテーブルを合体させて一度に複数のデータを持ってくるやり方。
	//テーブルの別名。メンバーテーブルのidと
	//下の文は、membersの中のnameというカラムとピクチャーというカラムとポストの中にあるもの全部（pが表している）を取りだそうとしている。
	//返信用のメッセージをるくるために、元のメッセージと投稿者	
		$page = $_REQUEST['page'];
		if($page ==''){
			$page =1;
		}
        $page = max($page,1);

	//最終ページを取得する
    $sq1 ='SELECT COUNT(*) AS cnt FROM posts';
    $recordSet = mysqli_query($db,$sq1);
    $table = mysqli_fetch_assoc($recordSet);
    $maxPage =ceil($table['cnt']/ 5);
    $page= min($page,$maxPage);

    $start=($page -1)*5;
    $start=max(0,$start);

	$sq1 = sprintf('SELECT m.name, m.picture, p.* FROM members m,posts p WHERE m.id=p.member_id ORDER BY p.created DESC LIMIT %d,5',
		$start
		);
		$posts= mysqli_query($db,$sq1)or die(mysqli_error($db));

	//返信の場合
		if(isset($_REQUEST['res'])){
			$sq1 = sprintf('SELECT m.name, m.picture, p.* FROM members m,posts p WHERE m.id=p.member_id AND p.id=%d ORDER BY p.created DESC',
				mysqli_real_escape_string($db,$_REQUEST['res'])
				);
			$record = mysqli_query($db,$sq1) or die(mysqli_error($db));
			$table = mysqli_fetch_assoc($record);
			$message = '@'.$table['name'].''.$table['message'];

	

		}

//htmlspecialcharsのショートカット
		function h($value){
			return htmlspecialchars($value,ENT_QUOTES,'UTF-8');
		} 

	//自作関数　htmlspecialcharsのショートカット
			function makeLink($value) {
				//URLになり得るものを指定している（＼＋＼；＼などの文字とは）
				return mb_ereg_replace("(https?)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)",'<a href="\1\2">\1\2</a>',$value);

	//関数の(中身)とは、「引数」と呼ばれている⇨　これから処理したい元となるもの。仕事に必要な情報。
	//戻り値とは、仕事が終わったあとに結果を教えてくれるもの。
	//function 名前（引数）{
	//	return　戻り値;
	
		}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="stylesheet" type="text/css" href="style.css" />
<title>ひとこと掲示板</title>
</head>

<body>
<div id="wrap">
	<div id="head">
		<h1>ひとこと掲示板</h1>
	</div>
	<div id="content">
		<div style="text-align: right"><a href="logout.php">ログアウト</a></div>
		<form action="" method="post">
			<dl>
				<dt><?php echo htmlspecialchars($member['name']); ?>さんメッセージをどうぞ</dt>
				<dd>
				<textarea name="message" cols="50" rows="5"><?php echo htmlspecialchars($message, ENT_QUOTES,'UTF-8');?></textarea>
				<input type="hidden" name="reply_post_id" value="<?php echo htmlspecialchars($_REQUEST['res'],ENT_QUOTES,'UTF-8'); ?>)"/>
				</dd>
			</dl>
			<div>
			<p>
				<input type="submit" value="投稿する" />
			</p>

		</form>

		<?php
		while($post= mysqli_fetch_assoc($posts)):
			?>
			<div class="msg">
				<img src="member_picture/<?php echo htmlspecialchars($post['picture'], ENT_QUOTES,'UTF-8'); ?>" width="48" height="48" alt="<?php echo htmlspecialchars($post['name'], ENT_QUOTES,'UTF-8'); ?>"/>
					<p><?php echo makeLink(h($post['message'])); ?><span class="name">(<?php echo htmlspecialchars($post['name'], ENT_QUOTES,'UTF-8'); ?>)</span></p>
					[<a href="index.php?res=<?php echo htmlspecialchars($post['id'], ENT_QUOTES,'UTF-8'); ?>">Re</a>]</p>
					<p class="day"> 
						<a href="view.php?id=<?php echo htmlspecialchars($post['id'], ENT_QUOTES,'UTF-8');?>"><?php echo htmlspecialchars($post['created'], ENT_QUOTES,'UTF-8');?></a>

						<?php
						if ($post['reply_post_id']> 0):
						?>
						<a href ="view.php?id=<?php echo 
						htmlspecialchars($post['reply_post_id'], ENT_QUOTES,'UTF-8'); ?>">
						返信元のメッセージ</a>
						<?php
						endif;
						?>

		<?php
		if($_SESSION['id'] == $post['member_id']):
		?>
    	[<a href="delete.php?id=<?php echo h($post['id']); ?>" style="color: #F33;">削除</a>]
    <?php
    endif;
    ?>
	 
		</p>
			</div>
			

		<?php
		endwhile;
		?>

	<ul class="paging">
	 	<?php
	 	if($page>1){
		?>
		<li><a href="index.php?page=<?php print ($page -1); ?>">前のページへ</a></li>
		<?php
		}else{
		?>
		<li>前のページへ</li>
		<?php
		}
		?>
		<?php
		if($page <$maxPage){
		?>
		<li><a href="index.php?page=<?php print ($page +1); ?>">次のページへ</a></li>
		</a></li>
		<?php
		}else{
		?>
		<li>次のページへ</li>
		<?php
		}
		?>
		</ul>

		<div class="msg">
			<img src="member_picture/me.jpg" width="48" height="48" alt="makoto" />
			<p>こんにちはです。<span class="name">(makoto)</span></p>
			<p class="day">2010/08/01 2:11</p>
		</div>	

		<div class="msg">

		   <img src="member_picture/dummy.png" width="48" height="48" alt="dummy" />

			<p>こんにちはです。<span class="name">（えりこ）</span>[<a href="index.html?res=1">Re</a>]</p>
			<p class="day">
				<a href="view.html?id=2">2015-10-10 12:00:00</a>
				<a href="view.html">返信元のメッセージ</a>
				[<a href="#" style="color: #F33;">削除</a>]
			</p>


		</div>

		<div class="msg">

			<img src="member_picture/dummy.png" width="48" height="48" alt="dummy" />

			<p>押忍！<span class="name">（しんや）</span>[<a href="index.html?res=1">Re</a>]</p>
			<p class="day">
				<a href="view.html?id=2">2015-10-10 12:00:00</a>
				[<a href="#" style="color: #F33;">削除</a>]
			</p>


		</div>

		<ul class="paging">
			<li><a href="#">前のページへ</a></li>
			<li><a href="#">次のページへ</a></li>
		</ul>
		</div><!-- <div id="content"> -->
	
	<div id="foot">
		<p><img src="images/txt_copyright.png" width="136" 	height="15" alt="(C) H2O SPACE, Mynavi" /></p>
	</div>
</div>
</body>
</html>
