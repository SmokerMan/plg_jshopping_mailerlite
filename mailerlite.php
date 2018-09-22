<?php

/**
 * JshoppingMailerLite
 * 
 * @version 	1.0	
 * @author	SmokerMan	 
 * @copyright	© 2018 All rights reserved. 
 * @license 	GNU/GPL v.3 or later.
 */

// no direct access
defined ( '_JEXEC' ) or die ( '(@)|(@)' );
class plgJshoppingMailerLite extends JPlugin {
	public function __construct(& $subject, $config) {
		parent::__construct ( $subject, $config );
		$this->loadLanguage ();
	}
	public function onBeforeDisplayRegisterView(&$view) {
		if (! $this->params->get ( 'show_register' )) {
			return;
		}
		$html = '
		<div><label>
			<input class="checkbox" name="mailerlite_subscribe" value="1" checked="checked" type="checkbox" />
			<span class="checkbox-custom"></span><span>' . $this->params->get ( 'text' ) . '</span>
		</label></div>';
		$view->_tmpl_register_html_5 .= $html;
	}
	public function onAfterRegister(&$user, &$usershop, &$post, &$useractivation) {
		$app = JFactory::getApplication ();
		$subscribe = $app->input->get ( 'mailerlite_subscribe', null, 'int' );
		if (! $subscribe) {
			return;
		}
		
		$addedSubscriber = $this->addSubscriber ( $usershop );
		
		if (! empty ( $addedSubscriber->error )) {
			$app->enqueueMessage ( $addedSubscriber->error->message, 'error' );
		}
	}
	public function onBeforeDisplayUsers(&$view) {
		if (! $this->params->get ( 'show_admin' )) {
			return;
		}
		
		$app = JFactory::getApplication ();
		
		$task = $app->input->get ( 'task' );
		if ($task == 'mailerlite_subscribe') {
			$total_send = 0;
			$ids = $app->input->get ( 'cid', null, 'array' );
			if ($ids) {
				$db = JFactory::getDbo ();
				$query = $db->getQuery ( true );
				$query->select ( '*' );
				$query->from ( '#__jshopping_users' );
				$query->where ( 'user_id IN (' . implode ( ',', $ids ) . ')' );
				$db->setQuery ( $query );
				$users = $db->loadObjectList ();
				if (! empty ( $users )) {
					foreach ( $users as $user ) {
						$addedSubscriber = $this->addSubscriber ( $user );
						if (! empty ( $addedSubscriber->error )) {
							$app->enqueueMessage ( $addedSubscriber->error->message, 'error' );
						} else {
							$total_send ++;
						}
					}
				}
			}
			$app->enqueueMessage ( JText::sprintf ( 'PLG_JSHOPPING_MAILERLITE_TOTAL_SEND', $total_send ), 'message' );
		}
		
		$view->tmp_html_col_after_email = '<th>' . JText::_ ( 'PLG_JSHOPPING_MAILERLITE_USERS_LABEL' ) . '</th>';
		
		JToolBarHelper::custom ( 'mailerlite_subscribe', 'save', '', JText::_ ( 'PLG_JSHOPPING_MAILERLITE_SUBSCRIBE_BTN' ), true );
		
		require_once JPATH_LIBRARIES . '/mailerlite/vendor/autoload.php';
		$subscribersApi = (new \MailerLiteApi\MailerLite ( $this->params->get ( 'key' ) ))->subscribers ();
		if (! empty ( $view->rows )) {
			foreach ( $view->rows as $row ) {
				if ($row->email) {
					$subscriber = $subscribersApi->find ( $row->email );
					if (empty ( $subscriber->id )) {
						$title = JText::_ ( 'PLG_JSHOPPING_MAILERLITE_NO_SUBSCRIBE' );
						$row->tmp_html_col_after_email = '<td class="center"><span class="icon-unpublish hasTooltip" title="' . $title . '" aria-hidden="true"></span></td>';
					} else {
						$status = JText::_ ( 'PLG_JSHOPPING_MAILERLITE_SUBSCRIBER_TYPE_' . $subscriber->type );
						$date_created = JHtml::_('date', $subscriber->date_created, 'd.m.Y H:i' );
						$date_updated = JHtml::_('date', $subscriber->date_updated, 'd.m.Y H:i' );
						$opened = $subscriber->opened . ' (' . ($subscriber->opened_rate * 100) . '%)';
						$clicked = $subscriber->clicked . ' (' . ($subscriber->clicked_rate * 100) . '%)';
						$title = JText::sprintf ( 'PLG_JSHOPPING_MAILERLITE_SUBSCRIBE', $status, $date_created, $date_updated, $subscriber->sent, $opened, $clicked );
						$row->tmp_html_col_after_email = '<td class="center"><span class="icon-publish hasTooltip" title="' . $title . '" aria-hidden="true"></span></td>';
					}
					// var_dump($subscriber);
				}
			}
		}
		// exit;
	}
	protected function addSubscriber($user) {
		$subscriber = array ();
		$subscriber ['email'] = $user->email;
		if (! empty ( $user->f_name )) {
			$subscriber ['fields'] ['name'] = $user->f_name;
		}
		if (! empty ( $user->l_name )) {
			$subscriber ['fields'] ['last_name'] = $user->l_name;
		}
		if (! empty ( $user->city )) {
			$subscriber ['fields'] ['city'] = $user->city;
		}
		if (! empty ( $user->phone )) {
			$subscriber ['fields'] ['phone'] = $user->phone;
		}
		require_once JPATH_LIBRARIES . '/mailerlite/vendor/autoload.php';
		$groupsApi = (new \MailerLiteApi\MailerLite ( $this->params->get ( 'key' ) ))->groups ();
		
		$addedSubscriber = $groupsApi->addSubscriber ( $this->params->get ( 'group_id' ), $subscriber );
		
		return $addedSubscriber;
	}
}