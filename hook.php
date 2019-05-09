<?php

function plugin_singlesignon_display_login() {
   global $CFG_GLPI;

   $signon_provider = new PluginSinglesignonProvider();

   $rows = $signon_provider->find('`is_active` = 1');

   $html = [];

   echo Html::css('/plugins/singlesignon/css/singlesignon.css');
   
   foreach ($rows as $row) {
      $query = [];

      if (isset($_REQUEST['redirect'])) {
         $query['redirect'] = $_REQUEST['redirect'];
      }

      $url = PluginSinglesignonProvider::getCallbackUrl($row['id'], $query);

      $html[] = '<a href="' . $url . '" class="singlesignon singlesignon-btn" > <span class="singlesignon-btn-label">' .
            'Entrar com o ' . $row['name'] . '</span> </a>';
      $html[] =  '<div class="singlesignon-terms"> Se clicar em "Entrar com o ' . $row['name'] .'" e não for usuário do GLPI, você será cadastrado e estará aceitando os Termos e condições e a Política de privacidade do ' . $row['name'] .'.</div>';
   }

   echo implode("<br />\n", $html);
   echo '<script type="text/javascript">
      $(".singlesignon").on("click", function (e) {
         e.preventDefault();

         var url   = $(this).attr("href");
         var left  = ($(window).width()/2)-(600/2);
         var top   = ($(window).height()/2)-(800/2);
         var newWindow = window.open(url, "singlesignon", "width=600,height=800,left=" + left + ",top=" + top);
         if (window.focus) {
            newWindow.focus();
         }
      });
       </script>';
}

function plugin_singlesignon_install() {
   /* @var $DB DB */
   global $DB;

   $currentVersion = '0.0.0';

   $default = [
   ];

   $current = Config::getConfigurationValues('singlesignon');

   if (isset($current['version'])) {
      $currentVersion = $current['version'];
   }

   foreach ($default as $key => $value) {
      if (!isset($current[$key])) {
         $current[$key] = $value;
      }
   }

   Config::setConfigurationValues('singlesignon', $current);

   if (!sso_TableExists("glpi_plugin_singlesignon_providers")) {
      $query = "CREATE TABLE `glpi_plugin_singlesignon_providers` (
                  `id`                         int(11) NOT NULL auto_increment,
                  `type`                       varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `name`                       varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `client_id`                  varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `client_secret`              varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `scope`                      varchar(255) COLLATE utf8_unicode_ci NULL,
                  `extra_options`              varchar(255) COLLATE utf8_unicode_ci NULL,
                  `url_authorize`              varchar(255) COLLATE utf8_unicode_ci NULL,
                  `url_access_token`           varchar(255) COLLATE utf8_unicode_ci NULL,
                  `url_resource_owner_details` varchar(255) COLLATE utf8_unicode_ci NULL,
                  `is_active`                  tinyint(1) NOT NULL DEFAULT '0',
                  `is_deleted`                 tinyint(1) NOT NULL default '0',
                  `comment`                    text COLLATE utf8_unicode_ci,
                  `date_mod`                   datetime DEFAULT NULL,
                  `date_creation`              datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `date_mod` (`date_mod`),
                  KEY `date_creation` (`date_creation`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->query($query) or die("error creating glpi_plugin_singlesignon_providers " . $DB->error());

      //      $query = "INSERT INTO `glpi_plugin_singlesignon_providers`
      //                       (`id`, `name`, `serial`, `is_deleted`)
      //                VALUES (1, 'example 1', 'serial 1', 0),
      //                       (2, 'example 2', 'serial 2', 0),
      //                       (3, 'example 3', 'serial 3', 0)";
      //      $DB->query($query) or die("error populate glpi_plugin_example " . $DB->error());
   }

   // add display preferences
   $query_display_pref = "SELECT id
      FROM glpi_displaypreferences
      WHERE itemtype = 'PluginSinglesignonProvider'";
   $res_display_pref = $DB->query($query_display_pref);
   if ($DB->numrows($res_display_pref) == 0) {
      $DB->query("INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginSinglesignonProvider','2','1','0');");
      $DB->query("INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginSinglesignonProvider','3','2','0');");
      $DB->query("INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginSinglesignonProvider','5','4','0');");
      $DB->query("INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginSinglesignonProvider','6','5','0');");
      $DB->query("INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginSinglesignonProvider','10','6','0');");
   }

   Config::setConfigurationValues('singlesignon', [
      'version' => PLUGIN_SINGLESIGNON_VERSION,
   ]);
   return true;
}

function plugin_singlesignon_uninstall() {
   global $DB;

   $config = new Config();
   $rows = $config->find("`context` LIKE 'singlesignon%'");

   foreach ($rows as $id => $row) {
      $config->delete(['id' => $id]);
   }

   // Old version tables
   if (sso_TableExists("glpi_plugin_singlesignon_providers")) {
      $query = "DROP TABLE `glpi_plugin_singlesignon_providers`";
      $DB->query($query) or die("error deleting glpi_plugin_singlesignon_providers");
   }

   return true;
}
