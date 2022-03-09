<?php
/*
** Zabbix
** Copyright (C) 2001-2022 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


/**
 * @var CView $this
 * @var array $data
 */

(new CWidget())->addItem(
	(new CTabs())
		->addItem(
			[
				(new CTab(
					(new CTag(
						'p',
						true,
						'First ipsum dolor sit amet consectetur adipisicing elit. Cum rerum, iusto adipisci commodi ipsum
						magnam
						blanditiis quas voluptas, repellat voluptatem quae vero asperiores autem beatae minus quibusdam
						explicabo
						voluptatum vel! First ipsum dolor sit amet consectetur adipisicing elit. Cum rerum, iusto adipisci commodi ipsum
						magnam
						blanditiis quas voluptas, repellat voluptatem quae vero asperiores autem beatae minus quibusdam
						explicabo
						voluptatum vel!'
					))
				))->setTitle('First')->setDisabled(true),
				(new CTab(
					(new CTag(
						'p',
						true,
						'Second ipsum dolor sit amet consectetur adipisicing elit. Cum rerum, iusto adipisci commodi ipsum
						magnam
						blanditiis quas voluptas, repellat voluptatem quae vero asperiores autem beatae minus quibusdam
						explicabo
						voluptatum vel! Second ipsum dolor sit amet consectetur adipisicing elit. Cum rerum, iusto adipisci commodi ipsum
						magnam
						blanditiis quas voluptas, repellat voluptatem quae vero asperiores autem beatae minus quibusdam
						explicabo
						voluptatum vel!'
					))
				))->setTitle('Second'),
				(new CTab(
					(new CTag(
						'p',
						true,
						'Third ipsum dolor sit amet consectetur adipisicing elit. Cum rerum, iusto adipisci commodi ipsum
						magnam
						blanditiis quas voluptas, repellat voluptatem quae vero asperiores autem beatae minus quibusdam
						explicabo
						voluptatum vel! Third ipsum dolor sit amet consectetur adipisicing elit. Cum rerum, iusto adipisci commodi ipsum
						magnam
						blanditiis quas voluptas, repellat voluptatem quae vero asperiores autem beatae minus quibusdam
						explicabo
						voluptatum vel!'
					))
				))->setTitle('Third'),
			]
		)
)->addItem(
	(new CTabs())
		->addItem(
			[
				(new CTab(
					(new CTag(
						'p',
						true,
						'First ipsum dolor sit amet consectetur adipisicing elit. Cum rerum, iusto adipisci commodi ipsum
						magnam
						blanditiis quas voluptas, repellat voluptatem quae vero asperiores autem beatae minus quibusdam
						explicabo
						voluptatum vel! First ipsum dolor sit amet consectetur adipisicing elit. Cum rerum, iusto adipisci commodi ipsum
						magnam
						blanditiis quas voluptas, repellat voluptatem quae vero asperiores autem beatae minus quibusdam
						explicabo
						voluptatum vel!'
					))
				))->setTitle('First'),
				new CTag('p', true,'Invalid tab test'),
				(new CTab(
					(new CTag(
						'p',
						true,
						'Second ipsum dolor sit amet consectetur adipisicing elit. Cum rerum, iusto adipisci commodi ipsum
						magnam
						blanditiis quas voluptas, repellat voluptatem quae vero asperiores autem beatae minus quibusdam
						explicabo
						voluptatum vel! Second ipsum dolor sit amet consectetur adipisicing elit. Cum rerum, iusto adipisci commodi ipsum
						magnam
						blanditiis quas voluptas, repellat voluptatem quae vero asperiores autem beatae minus quibusdam
						explicabo
						voluptatum vel!'
					))
				))->setTitle('Second'),
				(new CTab(
					(new CTag(
						'p',
						true,
						'Third ipsum dolor sit amet consectetur adipisicing elit. Cum rerum, iusto adipisci commodi ipsum
						magnam
						blanditiis quas voluptas, repellat voluptatem quae vero asperiores autem beatae minus quibusdam
						explicabo
						voluptatum vel! Third ipsum dolor sit amet consectetur adipisicing elit. Cum rerum, iusto adipisci commodi ipsum
						magnam
						blanditiis quas voluptas, repellat voluptatem quae vero asperiores autem beatae minus quibusdam
						explicabo
						voluptatum vel!'
					))
				))->setTitle('Third'),
			]
		)->setActiveTab(1)
)->addItem(
	(new CTabs())
		->addItem(
			[
				(new CTab(
					(new CTag(
						'p',
						true,
						'First ipsum dolor sit amet consectetur adipisicing elit. Cum rerum, iusto adipisci commodi ipsum
						magnam
						blanditiis quas voluptas, repellat voluptatem quae vero asperiores autem beatae minus quibusdam
						explicabo
						voluptatum vel! First ipsum dolor sit amet consectetur adipisicing elit. Cum rerum, iusto adipisci commodi ipsum
						magnam
						blanditiis quas voluptas, repellat voluptatem quae vero asperiores autem beatae minus quibusdam
						explicabo
						voluptatum vel!'
					))
				))->setTitle('First')->setDisabled(true),
				(new CTab(
					(new CTag(
						'p',
						true,
						'Second ipsum dolor sit amet consectetur adipisicing elit. Cum rerum, iusto adipisci commodi ipsum
						magnam
						blanditiis quas voluptas, repellat voluptatem quae vero asperiores autem beatae minus quibusdam
						explicabo
						voluptatum vel! Second ipsum dolor sit amet consectetur adipisicing elit. Cum rerum, iusto adipisci commodi ipsum
						magnam
						blanditiis quas voluptas, repellat voluptatem quae vero asperiores autem beatae minus quibusdam
						explicabo
						voluptatum vel!'
					))
				))->setTitle('Second')->setDisabled(true),
				(new CTab(
					(new CTag(
						'p',
						true,
						'Third ipsum dolor sit amet consectetur adipisicing elit. Cum rerum, iusto adipisci commodi ipsum
						magnam
						blanditiis quas voluptas, repellat voluptatem quae vero asperiores autem beatae minus quibusdam
						explicabo
						voluptatum vel! Third ipsum dolor sit amet consectetur adipisicing elit. Cum rerum, iusto adipisci commodi ipsum
						magnam
						blanditiis quas voluptas, repellat voluptatem quae vero asperiores autem beatae minus quibusdam
						explicabo
						voluptatum vel!'
					))
				))->setTitle('Third')->setDisabled(true),
			]
		)->setActiveTab(1)
)->show();
