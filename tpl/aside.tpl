<div class="theme" id="core-sidebar-header">
    <div id="core-sidebar-perfil">
        {if $loged}
            {if $login.imagem}
                <img src="{$home}image/{$login.imagem}&h=100&w=100" height="80" width="80" id="core-sidebar-perfil-img">
            {else}
                <div id="core-sidebar-perfil-img"><i class="material-icons">people</i></div>
            {/if}
            <div>
                {$login.nome}
            </div>
            <div>
                    <span>
                        {$login.email}
                    </span>
                <button id="btn-editLogin" style="margin-top: -13px">
                    <i class="material-icons">edit</i>
                    <span style="padding-right: 5px">perfil</span>
                </button>
            </div>
        {else}
            <i id="core-sidebar-perfil-img" class="material-icons">people</i>
            <div id="core-sidebar-name">
                Anônimo
            </div>
        {/if}
    </div>
</div>

<div id="core-sidebar-main">
    <ul id="core-applications"></ul>

    <ul id="core-sidebar-menu">
        {$menu}
        {if $loged}
            <li>
                <a href="{$home}dashboard">
                    Minha Conta
                </a>
            </li>
            <li>
                    <span onclick="logoutDashboard()">
                        sair
                    </span>
            </li>
        {else}
            <li>
                <a href="{$home}login">login</a>
            </li>
        {/if}
    </ul>
</div>