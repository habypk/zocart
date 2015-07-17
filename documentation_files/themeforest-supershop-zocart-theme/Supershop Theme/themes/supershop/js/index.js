/*
* 2007-2014 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2014 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

$(document).ready(function(){
//	$('#home-page-tabs li:first, #index .tab-content ul:first').addClass('active');
    
    /* Ovic code: Update Hometab-modules */
    
    // Home Featured module
    $(".option1 .tab-content #homefeatured").owlCarousel({
        loop:true,
        nav:true,
        //margin:30,
        responsive:{
            0:{
                items:1,
                margin:30
            },
            480:{
                items:2,
                margin:30
            },
            768:{
                items:3,
                margin:30
            },
            992:{
                items:4,
                margin:24
            },
            1200:{
                items:4,
                margin:30
            }
            
        }
    });
    
    // Block Bestsellers
    $(".option1 .tab-content #blockbestsellers").owlCarousel({
        loop:true,
        nav:true,
        //margin:30,
        responsive:{
            0:{
                items:1,
                margin:30
            },
            480:{
                items:2,
                margin:30
            },
            768:{
                items:3,
                margin:30
            },
            992:{
                items:4,
                margin:24
            },
            1200:{
                items:4,
                margin:30
            }
        }
    });
    
    // Block Newproducts
    $(".option1 .tab-content #blocknewproducts").owlCarousel({
        loop:true,
        nav:true,
        //margin:30,
        responsive:{
            0:{
                items:1,
                margin:30
            },
            480:{
                items:2,
                margin:30
            },
            768:{
                items:3,
                margin:30
            },
            992:{
                items:4,
                margin:24
            },
            1200:{
                items:4,
                margin:30
            }
        }
    });
    
    // Option2 
    $(".option2 .tab-content #homefeatured").owlCarousel({
        loop:true,
        nav:true,
        //margin:30,
        responsive:{
            0:{
                items:1,
                margin:30
            },
            480:{
                items:2,
                margin:30
            },
            768:{
                items:3,
                margin:13
            },
            992:{
                items:3,
                margin:24
            },
            1200:{
                items:4,
                margin:30
            }
        }
    });
    
    // Block Bestsellers
    $(".option2 .tab-content #blockbestsellers").owlCarousel({
        loop:true,
        nav:true,
        //margin:30,
        responsive:{
            0:{
                items:1,
                margin:30
            },
            480:{
                items:2,
                margin:30
            },
            768:{
                items:3,
                margin:13
            },
            992:{
                items:3,
                margin:24
            },
            1200:{
                items:4,
                margin:30
            }
        }
    });
    
    // Block Newproducts
    $(".option2 .tab-content #blocknewproducts").owlCarousel({
        loop:true,
        nav:true,
        //margin:30,
        responsive:{
            0:{
                items:1,
                margin:30
            },
            480:{
                items:2,
                margin:30
            },
            768:{
                items:3,
                margin:13
            },
            992:{
                items:3,
                margin:24
            },
            1200:{
                items:4,
                margin:30
            }
        }
    });
    
    // Option5 
    $(".option5 .tab-content #homefeatured").owlCarousel({
        loop:true,
        nav:true,
        //margin:30,
        responsive:{
            0:{
                items:1,
                margin:30
            },
            480:{
                items:2,
                margin:30
            },
            768:{
                items:3,
                margin:13
            },
            992:{
                items:3,
                margin:24
            },
            1200:{
                items:4,
                margin:30
            }
        }
    });
    
    // Block Bestsellers
    $(".option5 .tab-content #blockbestsellers").owlCarousel({
        loop:true,
        nav:true,
        //margin:30,
        responsive:{
            0:{
                items:1,
                margin:30
            },
            480:{
                items:2,
                margin:30
            },
            768:{
                items:3,
                margin:13
            },
            992:{
                items:3,
                margin:24
            },
            1200:{
                items:4,
                margin:30
            }
        }
    });
    
    // Block Newproducts
    $(".option5 .tab-content #blocknewproducts").owlCarousel({
        loop:true,
        nav:true,
        //margin:30,
        responsive:{
            0:{
                items:1,
                margin:30
            },
            480:{
                items:2,
                margin:30
            },
            768:{
                items:3,
                margin:13
            },
            992:{
                items:3,
                margin:24
            },
            1200:{
                items:4,
                margin:30
            }
        }
    });
    
    $('#home-page-tabs li:first, #index .tab-content .carousel-list:first').addClass('active');

});