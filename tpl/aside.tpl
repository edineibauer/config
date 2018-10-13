    <div class="theme" id="main-header-single-sidebar">
        <div id="perfil-sidebar">
            {if $loged}
                {if $login.imagem}
                    <img src="{$home}image/{$login.imagem}&h=100&w=100" height="80" width="80" id="single-sidebar-img-perfil">
                {else}
                    <div id="single-sidebar-img-perfil"><i class="material-icons">people</i></div>
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
                <i id="single-sidebar-img-perfil" class="material-icons">people</i>
                <div id="single-sidebar-name">
                    Anônimo
                </div>
            {/if}
        </div>
    </div>

    <div id="main-single-sidebar">
        <ul id="single-applications"></ul>
        {* <ul class="col border-bottom padding-bottom" id="actions">
             <li class="col pointer color-hover-grey-light">
                 <a href="{$home}dashboard" class="col padding-small padding-16">
                     <i class="material-icons left padding-right font-xlarge">notifications</i>
                     <span class="left padding-tiny">Notificações</span>
                 </a>
             </li>
         </ul>*}

        <ul id="menu">
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