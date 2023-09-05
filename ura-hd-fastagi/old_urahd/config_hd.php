<?php

return array(
    //'user_login' => $login,
    'user_login' => $login,
    //'pass_login' => $password,
    'pass_login' => $password,
    //'server_api' => $server,
    'server_api' => $server,
    'url_login' => 'ura-vale-api/auth/login',
    'siglaUra' => 'HD',
    'copiar_audios_para_teste' => 'true',
    'extensao_audio' =>'.wav',
    'audio_lib' => '/var/lib/asterisk/sounds/',
    'audio_ura' => 'uraHD/',
    'max_timeout' => '6000',
    'extensao_audio' =>'.wav',
    'max_tentativas' => '3',
    // URLS REDE
    'url_rede_valida_cnpjcpf' => 'ura-vale-api/services/uraRede/api01ValidaCnpjCpf',
    'url_ucc_abertura_ptcl_easy' => 'ura-vale-api/services/uraComum/api01AberturaProtocoloEasy',
    'url_urc_ant_receb' => 'ura-vale-api/services/uraRede/api02SimulaAntecipacaoRecebiveis',
    'url_urc_confirma_ant_receb' => 'ura-vale-api/services/uraRede/api03ConfirmarAntecipacaoRecebiveis',
    'url_rede_horario' => 'ura-vale-api/services/uraComum/api02ValidarHorarioAtendimento',
    'url_ucc_valida_senha' => 'ura-vale-api/services/uraComum/api05ValidarSenha',
    'url_ucc_altera_senha' => 'ura-vale-api/services/uraComum/api06AlterarSenha',
    'url_ucc_direciona_lig' => 'ura-vale-api/services/uraComum/api03DirecionarLigacaoAtendente',
    'url_tbc_valida_cartao' => 'ura-vale-api/services/uraBeneficio/api01ValidarCartaoTransacao',
    'url_ufc_vld_cartao_frota'=>'ura-vale-api/services/uraFrota/api01ValidarCartaoFrota',
    'url_ufc_autoriza_venda_frota'=>'ura-vale-api/services/uraFrota/api03AutorizaServicoVendaFrota',
    'url_ufc_vld_mtcl_motorista'=>'ura-vale-api/services/uraFrota/api02ValidarMatriculaMotoristaFrota',
    'url_tbc_autoriza_transacao'=>'ura-vale-api/services/uraBeneficio/api02AutorizaTransacao',
    'url_tbc_realiza_estorno'=> 'ura-vale-api/services/uraBeneficio/api04RealizaEstorno',
    'url_tbc_bsc_transacao'=>'ura-vale-api/services/uraBeneficio/api03BuscarTransacao',
    'url_ucc_protocolo_v2'=>'ura-vale-api/services/uraComum/api01AberturaProtocoloEasyV2'
);
