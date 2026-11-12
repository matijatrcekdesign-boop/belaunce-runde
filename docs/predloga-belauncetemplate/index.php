<?php
	/**
		* @package     Joomla.Site
		* @subpackage  Templates.belaunce
		*
		* @copyright   (C) 2022 Matija Trček
		* @license     GNU General Public License version 2 or later; see LICENSE.txt
		* This is a heavily stripped down/modified version of the default Cassiopeia template, designed to build new templates off of.
		* https://kevinsguides.com/guides/webdev/joomla4/joomla-4-templates/develop-j4-template
	*/
	
	
	defined('_JEXEC') or die;
	
	use Joomla\CMS\Factory;
	use Joomla\CMS\HTML\HTMLHelper;
	use Joomla\CMS\Language\Text;
	use Joomla\CMS\Table\Table;
	use Joomla\CMS\Helper\TagsHelper;
	
	
	/*Loading WebAssetManager*/
	
	$app = Factory::getApplication();
	$wa  = $this->getWebAssetManager();
	
	/*favicon*/
	$this->addHeadLink(HTMLHelper::_('image', 'favicon.ico', '', [], true, 1), 'icon', 'rel', ['type' => 'image/x-icon']);
	
	// Detecting Active Variables
	$option   = $app->input->getCmd('option', '');
	$view     = $app->input->getCmd('view', '');
	$layout   = $app->input->getCmd('layout', '');
	$task     = $app->input->getCmd('task', '');
	$itemid   = $app->input->getCmd('Itemid', '');
	$sitename = htmlspecialchars($app->get('sitename'), ENT_QUOTES, 'UTF-8');
    $menuDef = $app->getMenu();
	$menu     = $app->getMenu()->getActive();
	$pageclass = $menu !== null ? $menu->getParams()->get('pageclass_sfx', '') : '';
	$tagTable  = Table::getInstance('Tag', 'TagsTable');
	
	
	
	
	
	// Get this template's path
	$templatePath = 'templates/' . $this->template;
	
	/* frameworks - bootstrap + jquery */
	HTMLHelper::_('bootstrap.framework');
	HTMLHelper::_('jquery.framework');
	
	//Register our web assets (Css/JS)
	$wa->useStyle('template.belaunce.mainstyles');
	$wa->useScript('template.belaunce.scripts');
	
	//Set viewport
	$this->setMetaData('viewport', 'width=device-width, initial-scale=1');
	
	//get category name
	
	
?>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
	<head>
		<jdoc:include type="metas" />
		<jdoc:include type="styles" />
		<jdoc:include type="scripts" />
        
		
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
        <!-- Bootstrap core CSS -->
        <link href="<?php echo $templatePath; ?>/bootstrap_theme/bootstrap.css" rel="stylesheet" type="text/css"/>
        <link rel="stylesheet" href="<?php echo $templatePath; ?>/blocks.css"/>
        <!-- Custom styles for this template -->
        <link href="<?php echo $templatePath; ?>/style.css?v=<?php echo @filemtime(__DIR__ . '/style.css'); ?>" rel="stylesheet"/>
        <script src="https://unpkg.com/scrollreveal"></script>
	</head>
	<body class="text-black-50">
        <?php
$__app = \Joomla\CMS\Factory::getApplication();
$__in = $__app->getInput();
$__opt = $__in->getCmd('option'); $__view = $__in->getCmd('view');
$__user = $__app->getIdentity();
$__guest = !$__user || $__user->guest;
$__R = \Joomla\CMS\Router\Route::_('index.php?option=com_belauncerunde&view=runde');
$__M = \Joomla\CMS\Router\Route::_('index.php?option=com_belauncerunde&view=moje');
$__P = \Joomla\CMS\Router\Route::_('index.php?option=com_belauncerunde&view=prijava');
$__onR = ($__opt==='com_belauncerunde' && in_array($__view,['runde','runda',''],true)) || $__opt==='com_content';
$__onM = $__opt==='com_belauncerunde' && $__view==='moje';
$__onP = $__opt==='com_users' || ($__opt==='com_belauncerunde' && $__view==='prijava');
$__ic = [
 'runde' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10"></path></svg>',
 'moje' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3.5" y="4.5" width="17" height="16" rx="3"></rect><path d="M3.5 9h17M8 3v3M16 3v3"></path></svg>',
 'prof' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="3.6"></circle><path d="M5 20c0-3.3 3.1-6 7-6s7 2.7 7 6"></path></svg>',
];
?>
<header class="br-hdr">
 <div class="br-hdr__in">
  <?php if ($__opt==='com_belauncerunde' && in_array($__view,['runda','obrazec','prijava'],true)) : ?><a class="br-hdr__back" href="<?php echo htmlspecialchars($__R); ?>" aria-label="Nazaj na runde"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 5l-7 7 7 7"></path></svg></a><?php endif; ?>
  <a class="br-brand" href="<?php echo htmlspecialchars($__R); ?>"><img src="/templates/belauncetemplate/img/logo100.png" alt="Belaunce"/><span class="br-brand__sep"></span><span class="br-brand__t">Runde</span></a>
  <nav class="br-hnav" aria-label="Glavni meni">
   <a href="https://www.belaunce.cc">Domov</a>
   <a href="<?php echo htmlspecialchars($__R); ?>"<?php echo $__onR ? ' class="is-active" aria-current="page"' : ''; ?>>Runde</a>
   <a href="<?php echo htmlspecialchars($__M); ?>"<?php echo $__onM ? ' class="is-active" aria-current="page"' : ''; ?>>Moje runde</a>
  </nav>
  <?php if ($__guest) : ?><a class="br-hdr__login" href="<?php echo htmlspecialchars($__P); ?>">Prijava</a><?php else : ?><a class="br-hdr__login" href="<?php echo htmlspecialchars($__M); ?>">Moj profil</a><?php endif; ?>
  <a class="br-hdr__prof" href="<?php echo htmlspecialchars($__guest ? $__P : $__M); ?>" aria-label="<?php echo $__guest ? 'Prijava' : 'Moj profil'; ?>"><?php echo $__ic['prof']; ?></a>
 </div>
</header>
<nav class="br-tabbar" aria-label="Spodnja navigacija">
 <a href="<?php echo htmlspecialchars($__R); ?>" class="<?php echo $__onR ? 'is-active' : ''; ?>"><?php echo $__ic['runde']; ?><span>Runde</span></a>
 <a href="<?php echo htmlspecialchars($__M); ?>" class="<?php echo $__onM ? 'is-active' : ''; ?>"><?php echo $__ic['moje']; ?><span>Moje runde</span></a>
 <?php if (!$__guest) : ?>
 <a href="<?php echo htmlspecialchars(\Joomla\CMS\Router\Route::_('index.php?option=com_belauncerunde&view=obrazec&id=0')); ?>" class="<?php echo ($__opt==='com_belauncerunde' && $__view==='obrazec') ? 'is-active' : ''; ?>"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v8M8 12h8"></path></svg><span>Ustvari</span></a>
<?php endif; ?>
</nav>
        <main>
			<jdoc:include type="modules" name="top_html" /> 
          
          			<?php if ($this->countModules( 'top_html_style' )) : ?>
          <div class="container">
							<div class="row">
								<div class="col-md-12 my-3">
				<jdoc:include type="modules" name="top_html_style" /> 
			</div> </div> </div> 
			<?php endif; ?>
          
           <?php if ($this->countModules( 'top_koledar' )) : ?>
          <section class="bg-light">
          <div class="container">
							<div class="d-flex flex-row justify-content-center row">
								<div class="col-md-6 my-4">
				<jdoc:include type="modules" name="top_koledar" /> 
			</div> </div> </div> 
          </section>
			<?php endif; ?>
          
           <?php
              if ($menuDef->getActive() == $menuDef->getDefault()) 
              {
                $prvaBarvaClass = 'bg-light';
              }
              
              else 
              {
                $prvaBarvaClass = '';
              }
          ?>
          
          
<section class="joomla_content <?php echo $prvaBarvaClass; ?>">
    <div class="d-none">
        <?php
            //koda za preverjanje layouta
            echo $layout ;
            echo '<br/>' ;
            echo $option ;
            echo '<br/>' ;
            echo $view  ;
            echo '<br/>' ;
            if ($menuDef->getActive() == $menuDef->getDefault()) {
              echo 'prva stran';
            }
        ?>
    </div>

    <?php

        if ($option == 'com_blank' && $view == 'com_blank')
        {
        ?>
        <jdoc:include type="message" />
        <jdoc:include type="component" />
        <?php
        }

        elseif ($option == 'com_kisegonma')
        {
        ?>
        <section class="joomla_content_kisegonma">
            <jdoc:include type="message" />
            <jdoc:include type="component" />
        </section>
        <?php
        }

        elseif ($option == 'com_content' && ($view == 'article' || $view == 'category'))
        {
            //dobi tage glede na article id
            $article_id = $app->input->getInt('id');
            $th = new TagsHelper();
            $tagResult = $th->getItemTags('com_content.article', $article_id);

            //pretvori v array po stolpcu title, tu se skriva ime željenega taga
            $title_names = array_column($tagResult, 'title');

            if (in_array("purehtml", $title_names))
            {
            ?>
            <section class="joomla_content_html">
                <jdoc:include type="message" />
                <jdoc:include type="component" />
            </section>
            <?php
            }
            else
            {
            ?>
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <jdoc:include type="message" />
                        <jdoc:include type="component" />
                    </div>
                </div>
            </div>
            <?php
            }
        }

        else
        {
        ?>
        <div class="container my-4">
            <div class="row">
                <div class="col-md-12">
                    <jdoc:include type="message" />
                    <jdoc:include type="component" />
                </div>
            </div>
        </div>
        <?php
        }

    ?>

</section>
                    			           <?php if ($this->countModules( 'bot_koledar' )) : ?>
          				
          <section class="bg-light">
          <div class="container">
							<div class="d-flex flex-row justify-content-center row">
								<div class="col-md-6 my-4">
				<jdoc:include type="modules" name="bot_koledar" /> 
			</div> </div> </div> 
          </section>
			<?php endif; ?>
			
			<?php if ($this->countModules( 'bot_html_1' )) : ?>
				<jdoc:include type="modules" name="bot_html_1" /> 
			
			<?php endif; ?>
          
          
          

          
			
						<?php if ($this->countModules( 'bot_html_2' )) : ?>
						<section>
				<div class="belaunce-divider container-fluid  pb-4 pt-4">
					<div class="align-items-center  row">
						<div class="col ps-0">
							<div class="division"></div>                             
						</div>
						<div class="col-auto">
							<div> 
								<img src="/templates/belauncetemplate/img/logo100.png"/> 
							</div>                             
						</div>
						<div class="col pe-0">
							<div class="division"></div>                             
						</div>
					</div>
				</div>
			</section>
				<jdoc:include type="modules" name="bot_html_2" /> 
			
			<?php endif; ?>
          
          

			
			<?php if ($this->countModules( 'bot_html_3' )) : ?>
						<section>
				<div class="belaunce-divider container-fluid  pb-4 pt-4">
					<div class="align-items-center  row">
						<div class="col ps-0">
							<div class="division"></div>                             
						</div>
						<div class="col-auto">
							<div> 
								<img src="/templates/belauncetemplate/img/logo100.png"/> 
							</div>                             
						</div>
						<div class="col pe-0">
							<div class="division"></div>                             
						</div>
					</div>
				</div>
			</section>
				<jdoc:include type="modules" name="bot_html_3" /> 
			
			<?php endif; ?>
			

            <jdoc:include type="modules" name="bot_html_newsletter" />

		</main>
		<footer class="bg-dark pt-5 text-white"> 
          <jdoc:include type="modules" name="footer" />
            
		</footer>
		<script>
			
			ScrollReveal().reveal('.screveal');
			ScrollReveal().reveal('.screveal500', { delay: 500 });
			
			
			
			
			
		</script>
		<script src="<?php echo $templatePath; ?>/assets/js/popper.min.js"></script>
		
	</body>
</html>
