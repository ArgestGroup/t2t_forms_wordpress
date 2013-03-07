<?php

	/*
	Plugin Name: TB Tickets — Train&Bus tickets 
	Plugin URI: http://www.argest.com.ua
	Description: Встраиваемые формы для поиска и продажи ЖД и автобусных билетов от компании Аргест. Виджет легко встраивается, и позволяет продавать железнодорожные и автобусные билеты с Вашего сайта. В данный момент доступны билеты Украинских железных дорог и украинских автовокзалов.
	Партнерская программа — вознаграждение устанавливается самим партнером в виде наценки.
	Форма сотрудничества — на основании договора.
	Version: 0.9
	Author: Sergey Shuruta
	Author URI: http://www.argest.com.ua
	*/

	/*
	Copyright 2013 Argest Group LLC (email: info@argest.com.ua)
	
	This program is free software; you can redistribute it and/or modify
	it under the terms of the BSD Berkeley Software Distribution as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.
	*/

	include('t2t/T2TForms.php');

	class T2TFPlugin
	{
		public function __construct()
		{
			if(is_admin()) {
				T2TForms::app()->isShowEr(false);
			}

			// Извлекаем настройки
			if(isset($_POST['t2t_submit'])) {
				$variable = array(
					't2t_link_key' => $_POST['t2t_link_key'],
					't2t_domain' => $_POST['t2t_domain'],
					't2t_secret_key' => $_POST['t2t_secret_key'],
					't2t_lang' => $_POST['t2t_lang'],
					't2t_style' => $_POST['t2t_style'],
					't2t_style_jquery_ui' => $_POST['t2t_style_jquery_ui'],
					't2t_is_add_jquery' => (isset($_POST['t2t_is_add_jquery']) ? $_POST['t2t_is_add_jquery'] : 'no'),
					't2t_is_form_on_search' => (isset($_POST['t2t_is_form_on_search']) ? $_POST['t2t_is_form_on_search'] : 'no'),
				);
				update_option('t2t_options', $variable);
			}

			!is_array(get_option('t2t_options')) ? "" : extract(get_option('t2t_options'));
			$t2t_link_key = isset($t2t_link_key) ? $t2t_link_key : T2TForms::app()->getResultPage();
			$t2t_domain = isset($t2t_domain) ? $t2t_domain : '';
			$t2t_secret_key = isset($t2t_secret_key) ? $t2t_secret_key : T2TForms::app()->getSecretKey();
			$t2t_lang = isset($t2t_lang) ? $t2t_lang : T2TForms::getLang();
			$t2t_style = isset($t2t_style) ? $t2t_style : T2TForms::app()->getStyle();
			$t2t_style_jquery_ui = isset($t2t_style_jquery_ui) ? $t2t_style_jquery_ui : T2TForms::app()->getStyleJQueryUI();
			$t2t_is_add_jquery = isset($t2t_is_add_jquery) ? $t2t_is_add_jquery : 'yes';
			$t2t_is_form_on_search = isset($t2t_is_form_on_search) ? $t2t_is_form_on_search : 'yes';

			// Задаем страницу результатов
			T2TForms::app()->setResultPage($t2t_link_key);
			// Задаем роутер
			T2TForms::app()->setRouter(plugins_url('t2t_forms/t2t/T2TRouter.php'));
			// Задаем язык интерфейса
			T2TForms::app()->setLang($t2t_lang);
			// Задаем рабочий домен
			T2TForms::app()->setDomain($t2t_domain);
			// Задаем секретный ключь
			T2TForms::app()->setSecretKey($t2t_secret_key);
			// Обработка смены текущей платежной системы
			T2TForms::app()->paySystemSetter();
			// Уставновка стилей форм
			T2TForms::app()->setStyle($t2t_style);
			// Уставновка стилей jQuery UI
			T2TForms::app()->setStyleJQueryUI($t2t_style_jquery_ui);
			// Подгружать ли jQuery с сервера
			T2TForms::app()->isAddJQuery(($t2t_is_add_jquery == 'yes') ? true : false);
			// Отображать ли форму поиска на странице резульатов
			T2TForms::app()->isFormOnSearch(($t2t_is_form_on_search == 'yes') ? true : false);

			add_action('wp_loaded', array($this, 'intT2TForms'));
			add_shortcode('t2t_payments', array($this, 'wpPayments'));
			add_shortcode('t2t_form', array($this, 'wpForm'));
			add_shortcode('t2t_search', array($this, 'wpSearch'));
			add_shortcode('t2t_archive', array($this, 'wpArchive'));

			add_action('admin_menu', array($this, 'adminMenu'));
			add_action('wp_logout', array($this, 'wpLogout'));

		}
		
		public function adminMenu() {
			add_options_page('TB Tickets настройки', 'TB Tickets', 'manage_options', 't2t_forms_v0.9', array($this, 'pluginOptions'));
		}
		
		public function pluginOptions() {
			if ( !current_user_can( 'manage_options' ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}
			?>
			<div class="wrap">
				<form method="post">
					<?php screen_icon(); ?>
					<h2>Настройки TB Tickets</h2>
					<p>Для подключения форм необходимо создать два поста(или страницы). Первый будет содержать форму(ы) запроса, второй - результат поиска.
					Что бы отобразить форму поиска необходимо использовать следующий шоткод: <kbd>[t2t_form]</kbd>.
					По умолчанию отобразиться форма поиска по ЖД.
					Для явного указания типа поисковой формы, необходимо указать тип транспорта следующие образом: <kbd>[t2t_form transport="bus"]</kbd></p>
					<p>Для вывода результатов поиска используйте шоткод: <kbd>[t2t_search]</kbd></p>
					
					<p>В ряде случаев цена билета может изменяться в зависимости от типа платежной системы.
					Для этого есть смысл выводить панель доступных платежных систем, для предварительного выбора.
					Для вывода панели электронных платежных систем нужно добавить на страницу шоткод <kbd>[t2t_payments]</kbd>
					<p>Так же есть возможность отобразить пользователю его архив поездок.
					Эта опция возможна, только если пользователь авторизирован.
					Для отображения формы на странице необходимо добавить шоткод <kbd>[t2t_archive]</kbd></p>
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><label for="blogname">Cсылка на страницу результатов</label></th>
							<td>
								<input name="t2t_link_key" type="text" id="t2t_adress_key" value="<?=T2TForms::app()->getResultPage()?>" class="regular-text">
								<p class="description">Укажите ссылку на страницу содержащую подключение таблиц результатов</p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="blogname">Рабочий домен</label></th>
							<td>
								<input name="t2t_domain" type="text" id="t2t_domain" value="<?=T2TForms::getDomain()?>" class="regular-text">
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="blogname">Секретный ключ</label></th>
							<td>
								<input name="t2t_secret_key" type="text" id="t2t_secret_key" value="<?=T2TForms::getSecretKey()?>" class="regular-text">
								<p class="description">Что бы получить ключ обратитесь в <a href="http://www.argest.com.ua/" target="_blank"></a></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="blogname">Ссылка на стили форм</label></th>
							<td>
								<input name="t2t_style" type="text" id="t2t_style" value="<?=T2TForms::app()->getStyle()?>" class="regular-text">
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="blogname">Ссылка на стили jQueryUI форм</label></th>
							<td>
								<input name="t2t_style_jquery_ui" type="text" id="t2t_style_jquery_ui" value="<?=T2TForms::app()->getStyleJQueryUI()?>" class="regular-text">
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">Скрипт jQuery</th>
							<td>
								<fieldset>
									<label>
										<input name="t2t_is_add_jquery" type="checkbox" value="yes" <?=(T2TForms::app()->isAddJQuery() ? ' checked="checked"' : '')?> />
										подгружать с сервера
									</label>
								</fieldset>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">Форма поиска</th>
							<td>
								<fieldset>
									<label>
										<input name="t2t_is_form_on_search" type="checkbox" value="yes" <?=(T2TForms::app()->isFormOnSearch() ? ' checked="checked"' : '')?> />
										Отображать форму поиска на странице резульатов
									</label>
								</fieldset>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="blogname">Укажите язык интерфейса</label></th>
							<td>
								<select name="t2t_lang" id="default_role">
									<option <?=((T2TForms::app()->getLang() == T2TForms::LANG_RU) ? 'selected="selected"' : '')?> value="<?=T2TForms::LANG_RU?>"><?=T2TForms::LANG_RU?></option>
									<option <?=((T2TForms::app()->getLang() == T2TForms::LANG_UA) ? 'selected="selected"' : '')?> value="<?=T2TForms::LANG_UA?>"><?=T2TForms::LANG_UA?></option>
									<option <?=((T2TForms::app()->getLang() == T2TForms::LANG_EN) ? 'selected="selected"' : '')?> value="<?=T2TForms::LANG_EN?>"><?=T2TForms::LANG_EN?></option>
									<option <?=((T2TForms::app()->getLang() == T2TForms::LANG_DE) ? 'selected="selected"' : '')?> value="<?=T2TForms::LANG_DE?>"><?=T2TForms::LANG_DE?></option>
								</select>
							</td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" name="t2t_submit" id="submit" class="button button-primary" value="Сохранить изменения">
					</p>
				</form>
			</div>
			<?php
		}
	
		public function intT2TForms()
		{
			// Извлекаем данные активного пользователя
			global $current_user;
			get_currentuserinfo();
			
			// Если пользователь активен на сайте
			if ( 0 != $current_user->ID ) {
				// Устанавливаем email текущего пользователя
				T2TForms::app()->setUEmail($current_user->data->user_email);
				// Устанавливаем Имя текущего пользователя
				T2TForms::app()->setUName($current_user->first_name);
				// Устанавливаем Фамилию текущего пользователя
				T2TForms::app()->setUSurName($current_user->last_name);
			}

			// Выводим css
			$cssLinks = T2TForms::app()->getCssLinks();
			if($cssLinks)
				foreach ($cssLinks as $index => $link) {
					wp_enqueue_style('t2t' . $index, $link);
				}

			// Выводим js
			$jsLinks = T2TForms::app()->getJsLinks();
			if($jsLinks)
				foreach ($jsLinks as $index => $link) {
					wp_enqueue_script('t2t' . $index, $link, array('jquery'));
				}
		}

		public function wpPayments($atts, $content = null)
		{
			return T2TForms::app()->getPaySystems();
		}
		
		public function wpForm($atts, $content = null)
		{
			$type = (isset($atts['transport']) && in_array($atts['transport'], array('train', 'bus'))) ? $atts['transport'] : 'train';
			return T2TForms::app()->getForm($type);
		}
		
		public function wpSearch($atts, $content = null)
		{
			return T2TForms::app()->getTable();
		}
		
		public function wpArchive($atts, $content = null)
		{
			return T2TForms::app()->getArchive();
		}

		public function wpLogout()
		{
			T2TForms::logout(true);
		}
	}
	
	$T2TFPlugin = new T2TFPlugin();

?>