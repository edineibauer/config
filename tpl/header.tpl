<div class="header-container">
    <header class="header-logo">
        <a href="{$home}">
            {if $logo != "" && $logo != $home}
                <img src="{$logo}" alt="logo do site {$sitename}"
                     title="{$sitename} {($sitesub != "") ? " - $sitesub" : ""}" height="39" class="header-img">
                <h1 style="font-size:0">{$sitename}</h1>
            {elseif $favicon && $favicon != $home}
                <img src="{$favicon}" height="35" style="height: 35px" class="header-img">
                <h1 class="header-title">{$sitename}</h1>
            {else}
                <h1 class="header-title">{$sitename}</h1>
            {/if}
        </a>
    </header>
    <nav role="navigation">
        <ul class="header-nav">
            {$menu}
            {if $loged}
                <li>
                    <a href="{$home}dashboard">minha conta</a>
                </li>
                <li>
                    <span onclick="logoutDashboard()">SAIR</span>
                </li>
            {else}
                <li>
                    <a href="{$home}login">login</a>
                </li>
            {/if}

            <li id="open-menu" onclick="toggleSidebar()">
                <div class="menu icon" data-before="menu" data-after="remove"></div>
            </li>
        </ul>
    </nav>
</div>