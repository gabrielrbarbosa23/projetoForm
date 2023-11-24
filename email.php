<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário de Briefing</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

    <link rel="stylesheet" type="" href="styleEmail.css" />
</head>

<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarEmail($DADOS, $dataFinal, $outrosServ, $arquivos, $email){

    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';

    $username   	= "";                               //repetir o email do remetente
    $password   	= "";                               //ver documentação do gmail - senha no formato: xxxx xxxx xxxx xxxx
    $smtpsecure 	= PHPMailer::ENCRYPTION_STARTTLS;
    $smtpserver		= '';                               //ver documentação do gmail (geralmente smtp.gmail.com)
    $smtpport		= '';                               //ver documentação do gmail (geralmente porta 587)
    $from   		= "";                               //email remetente (gmail precisa habilitar smtp)
    $fromname 		= "";                               //Nome remetente

    $mail = new PHPMailer();

     //Parametros de conexão
     $mail->SMTPSecure 		= $smtpsecure;
     $mail->Host				= $smtpserver;
     $mail->Port				= $smtpport;  
     $mail->Username   		= $username;
     $mail->Password   		= $password;
     //$mail->SMTPKeepAlive 	= true;  
     //$mail->Mailer 			= "smtp"; 
     $mail->IsSMTP(); 
     $mail->SMTPAuth   		= true; 
     //$mail->CharSet 			= 'iso-8859-1';  
     $mail->SMTPDebug  		= 0; 
     //$mail->SetLanguage 		= "br";
     //$mail->WordWrap    		= 80;
     //$mail->Timeout 	 	 	= 60; 
     //$mail->SMTPAutoTLS		= 1;
 
     //Debug
     //$mail->SMTPDebug 		    = 2;
     //$mail->Debugoutput 		= 'html';
     
     //From  
     $mail->From     		= $from;
     $mail->FromName 		= $fromname;
     $mail->AddAddress($email);

    //<!---------------------------------- Enviar arquivos anexados ------------------------------>

    $tam = count($arquivos['name']);
    if ($tam > 0) {
      
        $i = 0;

        while ($i < $tam) {
            $mail->addAttachment($arquivos['tmp_name'][$i],
                                 $arquivos['name'][$i]);

            $i++;
        }
    }

    $mail->CharSet = 'UTF-8';
    $mail->Subject          = 'Formulário de "Briefing"';
    $mail->IsHTML(true);

    $body = '<!DOCTYPE html>
                <html lang="pt-br">
                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>Document</title>
                    </head>
                    <body>
                        <h1>Dados do Formulário:</h1>
                        <p><strong>Projeto:</strong> ' . $DADOS['projeto'] . '</p>
                        <p><strong>Colaborador:</strong> ' . $DADOS['colaborador'] . '</p>
                        <p><strong>Setor:</strong> ' . $DADOS['setor'] . '</p>
                        <p><strong>Gerente:</strong> ' . $DADOS['gerente'] . '</p>
                        <p><strong>Diretor:</strong> ' . $DADOS['diretor'] . '</p>
                        <p><strong>Data:</strong> ' . (new DateTime($DADOS['data']))->format('d-m-Y') . '</p>
                        <p><strong>Ideia:</strong> ' . $DADOS['ideia'] . '</p>
                        <p><strong>Sugestões:</strong> ' . $dataFinal . '</p>
                        <p><strong>Outras sugestões:</strong> ' . (isset($_POST['checkOutros']) ? $_POST['checkOutros'] : '') . '</p>
                        <p><strong>Outros Serviços:</strong> ' . implode(', ', $outrosServ) . '</p>
                        <p><strong>Materiais:</strong> ' . $DADOS['materiais'] . '</p>
                    </body>
                </html>';

    $bodyTrello =       'Projeto: ' . mb_convert_encoding($DADOS['projeto'], "ISO-8859-1", "UTF-8") . '<br> ' .
                        'Colaborador: ' . mb_convert_encoding($DADOS['colaborador'], "ISO-8859-1", "UTF-8")  . '<br> ' .
                        mb_convert_encoding($dataFinal, "ISO-8859-1", "UTF-8");

    $mail->Body             =  $body;

    if(!$mail->Send()) {
        $enviado = false;
        ?>
        <div style="width:40%" class="alert alert-danger" role="alert">
            <?php print 'Não foi enviado!!!'; ?>
            <br><br>
            <?php print $mail->ErrorInfo ?>
        </div>
        <?php
    } else {

        ?>
        <br><br>
        <div style="width:40%" class="alert alert-success" role="alert">
            <?php print 'Sua solicitação foi registrada com sucesso!!! Em breve você receberá por e-mail a confirmação de sua demanda e o prazo de entrega do seu conteúdo.'; ?>
        </div>
        <?php
        $mail->ClearAllRecipients();
        $mail->clearAttachments();
        $mail->AddAddress('');          //Ver o email do quadro no trello que deseja as informações
        $mail->IsHTML(true);
        $mail->Subject = mb_convert_encoding('Formulário de "Briefing', "ISO-8859-1", "UTF-8");
        $mail->CharSet = 'ISO-8859-1';
        $mail->Body = $bodyTrello;
        $mail->Send();
    }
}

    // Neste ponto do programa vou pegar a variavel de retorno post, ler os dados e enviar o email

if(isset($_POST) && isset($_POST['data'])){

    //<!---------------------------------- Função dos prazos em dias úteis ------------------------------>

    function obter_Feriados_API() {
        $url = 'https://brasilapi.com.br/api/feriados/v1/2023'; // Substitua pela URL da API de feriados
        $feriados = file_get_contents($url); // Faça a requisição para obter os feriados
        $feriados = json_decode($feriados, true);

        $dates = array(); // Array para armazenar apenas as datas dos feriados

        foreach ($feriados as $feriado) {
            $dates[] = $feriado['date']; // Adiciona a data do feriado ao array $dates
        }

        return $dates; // Retorna somente as datas dos feriados como um array
    }

    $feriados = obter_Feriados_API();

    function calcular_prazo_formulario($dataInicial, $dias, $descricao, $feriados) {
        $data = new DateTime($dataInicial);
        
        // Loop para adicionar dias úteis
        for ($i = 0; $i < $dias; $i++) {
            $data->modify('+1 day');
        
            // Verificar se o dia da semana é sábado (6) ou domingo (0)
            if ($data->format('N') == 6 || $data->format('N') == 7 || in_array($data->format('Y-m-d'), $feriados)) {
                $i--; // Subtrair 1 do contador para compensar o dia não útil
            }
        }
        
        return $descricao . ' <strong>Prazo de entrega do seu conteúdo: </strong> ' . $data->format('d-m-Y'); // Retornar a data final
    }

    $totalDias = 0;
    $outrosServ = array();
    $descricao = '';

    if (isset($_POST['tv'])){
        $totalDias = $totalDias + explode("|", $_POST['tv'])[1];
        $descricao .= explode("|", $_POST['tv'])[0] . '<br>';
    }

    if (isset($_POST['intranet'])){
        $totalDias = $totalDias + explode("|", $_POST['intranet'])[1];
        $descricao .= explode("|", $_POST['intranet'])[0] . '<br>';
    }

    if (isset($_POST['redePost'])){
        $totalDias = $totalDias + explode("|", $_POST['redePost'])[1];
        $descricao .= explode("|", $_POST['redePost'])[0] . '<br>';
    }

    if (isset($_POST['redeReels'])){
        $totalDias = $totalDias + explode("|", $_POST['redeReels'])[1];
        $descricao .= explode("|", $_POST['redeReels'])[0] . '<br>';
    }

    if (isset($_POST['site'])){
        $totalDias = $totalDias + explode("|", $_POST['site'])[1];
        $descricao .= explode("|", $_POST['site'])[0] . '<br>';
    }

    if (isset($_POST['comunicacao'])){
        $totalDias = $totalDias + explode("|", $_POST['comunicacao'])[1];
        $descricao .= explode("|", $_POST['comunicacao'])[0] . '<br>';
    }

    if (isset($_POST['email'])){
        $totalDias = $totalDias + explode("|", $_POST['email'])[1];
        $descricao .= explode("|", $_POST['email'])[0] . '<br>';
    }

    if (isset($_POST['adesivacaoBanner'])){
        $totalDias = $totalDias + explode("|", $_POST['adesivacaoBanner'])[1];
        $descricao .= explode("|", $_POST['adesivacaoBanner'])[0] . '<br>';
    }

    if (isset($_POST['adesivacaoTesteira'])){
        $totalDias = $totalDias + explode("|", $_POST['adesivacaoTesteira'])[1];
        $descricao .= explode("|", $_POST['adesivacaoTesteira'])[0] . '<br>';
    }

    if (isset($_POST['cartaz'])){
        $totalDias = $totalDias + explode("|", $_POST['cartaz'])[1];
        $descricao .= explode("|", $_POST['cartaz'])[0] . '<br>';
    }

    if (isset($_POST['adesivos'])){
        $totalDias = $totalDias + explode("|", $_POST['adesivos'])[1];
        $descricao .= explode("|", $_POST['adesivos'])[0] . '<br>';
    }

    if (isset($_POST['catalogo'])){
        $totalDias = $totalDias + explode("|", $_POST['catalogo'])[1];
        $descricao .= explode("|", $_POST['catalogo'])[0] . '<br>';
    }

    if (isset($_POST['videos'])){
        $totalDias = $totalDias + explode("|", $_POST['videos'])[1];
        $descricao .= explode("|", $_POST['videos'])[0] . '<br>';
    }

    if (isset($_POST['folder'])){
        $totalDias = $totalDias + explode("|", $_POST['folder'])[1];
        $descricao .= explode("|", $_POST['folder'])[0] . '<br>';
    }

    if (isset($_POST['outros'])) {
        $outrosInfo = isset($_POST['checkOutros']) ? $_POST['checkOutros'] : 'Outros';
        $outrosArray = explode("|", $_POST['outros']);
        $totalDias += isset($outrosArray[1]) ? $outrosArray[1] : 0;
        $outrosSuges[] = $outrosInfo;
    }

    $dataFinal = calcular_prazo_formulario($_POST['data'], $totalDias, $descricao, $feriados);
    //print $dataFinal; debug prazos

    //<!---------------------------------- checkbox outros serviços ------------------------------>
    if (isset($_POST['foto']))
        $outrosServ[] = 'Foto';

    if (isset($_POST['video']))
        $outrosServ[] = 'Vídeo | Animação Gráfica';

    if (isset($_POST['brindes']))
        $outrosServ[] = 'Brindes corporativos';

    if (isset($_POST['outrosBox']) && !empty($_POST['checkOutrosServ']))
        $outrosServ[] = '<strong>Outros: </strong> ' . $_POST['checkOutrosServ'];
    
    //enviando todas as informações no email
    enviarEmail($_POST, $dataFinal, $outrosServ, $_FILES['referencia'], $_POST['emailSend']);
    die();
}

?>

<body>
<!---------------------------------- Campo de formulário (projeto) ----------------------------------->

        <section class="content">
            <div class="formulario">
                    <form class="form" method="post" action="email.php" enctype="multipart/form-data">
                        <input type="hidden" name="emailSend" value="<?php print ($_POST['email']) ?>">

                        <div class="inputbox">
                            <label class="title" for="projeto">PROJETO | CAMPANHA: *</label>
                            <input name="projeto" type="text" class="inputUser" id="projeto" placeholder="Sua resposta" required>
                        </div>

<!---------------------------- Campo de formulário (Colaborador) ------------------------------------->

                        <div class="inputbox">
                            <label class="title" for="colaborador">COLABORADOR (A): *</label>
                            <input name="colaborador" type="text" class="inputUser" id="colaborador" placeholder="Sua resposta" required>
                        </div>

<!------------------------------------------ Select de setor ----------------------------------------->

                        <div class="inputbox">
                            <label class="title" for="setor">SETOR: *</label>
                            <select name="setor" id="setor" required>
                                <option value="">Selecione o setor</option>
                                <option value="Abastecimento">Abastecimento</option>
                                <option value="Acabamento">Acabamento</option>
                                <option value="Adm.Industrial">Adm.Industrial</option>
                                <option value="Adm.Vendas">Adm.Vendas</option>
                                <option value="Apoio">Apoio</option>
                                <option value="Centro de distribuição">Centro de distribuição</option>
                                <option value="Comercial">Comercial</option>
                                <option value="Corte">Corte</option>
                                <option value="Costura">Costura</option>
                                <option value="Cronoanálise">Cronoanálise</option>
                                <option value="Diretoria">Diretoria</option>
                                <option value="Engenharia de produto">Engenharia de produto</option>
                                <option value="Estamparia">Estamparia</option>
                                <option value="Estilo">Estilo</option>
                                <option value="Financeiro e contabilidade">Financeiro e contabilidade</option>
                                <option value="Formação de lote">Formação de lote</option>
                                <option value="Gestão">Gestão</option>
                                <option value="Logística">Logística</option>
                                <option value="Manutenção">Manutenção</option>
                                <option value="Marketing">Marketing</option>
                                <option value="PCP">PCP</option>
                                <option value="Planejamento">Planejamento</option>
                                <option value="Qualidade">Qualidade</option>
                                <option value="Recursos Humanos">Recursos Humanos</option>
                                <option value="Sublimação">Sublimação</option>
                                <option value="Suprimentos">Suprimentos</option>
                                <option value="TI">TI</option>
                            </select>
                        </div>

<!---------------------------------- Select de Gerente/supervisor ------------------------------------>

                        <div class="inputbox">
                            <label class="title" for="gerente">VALIDADO PELO GERENTE | SUPERVISOR (A): *</label>
                            <select name="gerente" id="gerente" required>
                                <option value="">Selecione o gerente/supervisor</option>
                                <option value="Gerente 1 - Gerente de Engenharia de Produto">Gerente 1 - Gerente de Engenharia de Produto</option>
                                <option value="Gerente 2 - Gerente de Logística">Gerente 2 - Gerente de Logística</option>
                                <option value="Gerente 3 - Supervisora de Marketing">Gerente 3 - Supervisora de Marketing</option>
                                <option value="Gerente 4 - Gerente Comercial">Gerente 4 - Gerente Comercial</option>
                                <option value="Gerente 5 - Gerente de TI">Gerente 5 - Gerente de TI</option>
                                <option value="Gerente 6 - Gerente de RH, Gestão e Apoio">Gerente 6 - Gerente de RH, Gestão e Apoio</option>
                                <option value="Gerente 7 - Gerente de Planejamento, Suprimentos, PCP e Abastecimento">Gerente7 - Gerente de Planejamento, Suprimentos, PCP e Abastecimento</option>
                                <option value="Gerente 8 - Gerente de Estilo">Gerente 8 - Gerente de Estilo</option>
                                <option value="Gerente 9 - Gerente Financeiro, Contabilidade e Adm. de Vendas">Gerente9 - Gerente Financeiro, Contabilidade e Adm. de Vendas</option>
                                <option value="Gerente 10 - Gerente de Produção">Gerente 10 - Gerente de Produção</option>
                            </select>
                        </div>

<!---------------------------------- Select de Diretor ----------------------------------------------->

                        <div class="inputbox">
                            <label class="title" for="diretor">VALIDADO PELO DIRETOR (A): *</label>
                            <select name="diretor" id="diretor" required>
                                <option value="">Selecione o diretor</option>
                                <option value="Diretor 1 - Diretor de Negócios">Diretor 1 - Diretor de Negócios</option>
                                <option value="Diretor 2 - Diretor Geral">Diretor 2 - Diretor Geral</option>
                                <option value="Diretor 3 - Diretora de Produção">Diretor 3 - Diretora de Produção</option>
                            </select>
                        </div>

<!---------------------------------- Campo de Data --------------------------------------------------->

                        <div class="inputbox">
                            <label class="title" for="data">DATA DE REALIZAÇÃO DA AÇÃO | CAMPANHA | PROJETO: *</label>
                            <input name="data" type="date" class="inputUser" id="data" placeholder="Sua resposta" required>
                        </div>

<!---------------------------------- campo de formulário (Ideia) ------------------------------------->

                        <div class="inputbox">
                            <label class="title" for="ideia">DESCREVA SUA IDEIA | INSPIRAÇÃO: *</label>
                            <input name="ideia" type="text" class="inputUser" id="ideia" placeholder="Sua resposta" required>
                        </div>

<!---------------------------------- Campo de formulário (Referencia) -------------------------------->

                        <div class="inputbox">
                            <h1 class="title">SE PREFERIR INSIRA UMA REFERÊNCIA: </h1>
                            <label class="refLabel" for="referencia">Adicionar arquivo</label>
                            <input name="referencia[]" type="file" multiple id="referencia">
                            <span id="nome"></span>
                            <p id="nomeArquivo"></p>
                        </div>
                        

<!---------------------------------- checkbox de sugestão (prazo) ------------------------------------>

                        <div class="inputbox">

                            <h1 class="title">SUGESTÃO DE CANAL PARA DIVULGAÇÃO: *</h1>
                            <h5>Fique atento aos prazos de execução que envolvem a participação de outros setores, tais como Compras, Plotter e Gráficas externas.</h5>

                            <div class="inputCheckbox">
                                <input name="tv" type="checkbox" class="box" id="tv" value="TV Corporativa (Prazo  mínimo - 7 dias úteis)|7">
                                <label class="label" for="seteD">TV Corporativa (Prazo  mínimo - 7 dias úteis)</label>
                            </div>

                            <div class="inputCheckbox">
                                <input name="intranet" type="checkbox" class="box" id="intranet" value="Intranet (Prazo  mínimo - 3 dias úteis)|3">
                                <label class="label" for="tresD">Intranet (Prazo  mínimo - 3 dias úteis)</label>
                            </div>
                           
                            <div class="inputCheckbox">
                                <input name="redePost" type="checkbox" class="box" id="redePost" value="Redes Sociais - Post/Story (Prazo  mínimo  - 3 dias úteis)|3">
                                <label class="label" for="tresD">Redes Sociais - Post/Story (Prazo  mínimo  - 3 dias úteis)</label>
                            </div>

                            <div class="inputCheckbox">
                                <input name="redeReels" type="checkbox" class="box" id="redeReels" value="Redes Sociais - Carrossel/Reels (Prazo  mínimo  - 5  dias úteis)|5">
                                <label class="label" for="cincoD">Redes Sociais - Carrossel/Reels (Prazo  mínimo  - 5  dias úteis)</label>
                            </div>

                            <div class="inputCheckbox">
                                <input name="site" type="checkbox" class="box" id="site" value="Site (Digital)- Banner/Testeira/Produção de Conteúdo (Prazo  mínimo - 7 dias úteis)|7">
                                <label class="label" for="seteD">Site (Digital)- Banner/Testeira/Produção de Conteúdo (Prazo  mínimo - 7 dias úteis)</label>
                            </div>

                            <div class="inputCheckbox">
                                <input name="comunicacao" type="checkbox" class="box" id="comunicacao" value="Comunicados via Whatsapp (Prazo  mínimo - 3 dias úteis)|3">
                                <label class="label" for="tresD">Comunicados via Whatsapp (Prazo  mínimo - 3 dias úteis)</label>
                            </div>

                            <div class="inputCheckbox">
                                <input name="email" type="checkbox" class="box" id="email" value="E-mail Marketing (Prazo  mínimo  - 3 dias úteis)|3">
                                <label class="label" for="tresD">E-mail Marketing (Prazo  mínimo  - 3 dias úteis)</label>
                            </div>

                            <div class="inputCheckbox">
                                <input name="adesivacaoBanner" type="checkbox" class="box" id="adesivacaoBanner" value="Adesivação - Ambiência (Impresso) - Banner/Cartaz/Backdrop (Prazo  mínimo  - 5 dias úteis)|5">
                                <label class="label" for="cincoD">Adesivação | Ambiência (Impresso) - Banner/Cartaz/Backdrop (Prazo  mínimo  - 5 dias úteis)</label>
                            </div>

                            <div class="inputCheckbox">
                                <input name="adesivacaoTesteira" type="checkbox" class="box" id="adesivacaoTesteira" value="Adesivação - Ambiência (Impresso) - Testeiras/Placas/Faixas (Prazo  mínimo  - 3 dias úteis)|3">
                                <label class="label" for="tresD">Adesivação | Ambiência (Impresso) - Testeiras/Placas/Faixas (Prazo  mínimo  - 3 dias úteis)</label>
                            </div>

                            <div class="inputCheckbox">
                                <input name="cartaz" type="checkbox" class="box" id="cartaz" value="Cartaz (A3 - A4) - Carta - Convite - Informativo (impresso) - (Prazo  mínimo  - 5 dias úteis)|5">
                                <label class="label" for="cincoD">Cartaz (A3 | A4) | Carta | Convite | Informativo (impresso) - (Prazo  mínimo  - 5 dias úteis)</label>
                            </div>

                            <div class="inputCheckbox">
                                <input name="adesivos" type="checkbox" class="box" id="adesivos" value="Adesivos e Personalização de brindes - (Prazo  mínimo - 3 dias úteis)|3">
                                <label class="label" for="tresD">Adesivos e Personalização de brindes - (Prazo  mínimo - 3 dias úteis)</label>
                            </div>

                            <div class="inputCheckbox">
                                <input name="catalogo" type="checkbox" class="box" id="catalogo" value="Catálogo (Impresso - digital)  - (Prazo mínimo - 30 dias úteis)|30">
                                <label class="label" for="trintaD">Catálogo (Impresso | digital)  - (Prazo  mínimo  - 30 dias úteis)</label>
                            </div>

                            <div class="inputCheckbox">
                                <input name="videos" type="checkbox" class="box" id="videos" value="Vídeos - Cobertura de Eventos - (Prazo  mínimo - 15 dias úteis)|15">
                                <label class="label" for="quinzeD">Vídeos | Cobertura de Eventos - (Prazo  mínimo - 15 dias úteis)</label>
                            </div>

                            <div class="inputCheckbox">
                                <input name="folder" type="checkbox" class="box" id="folder" value="Folder (Impresso) - 2 dobras - 4 dobras - 6 dobras (Prazo mínimo - 7 dias úteis)|7">
                                <label class="label" for="seteD">Folder (Impresso) - 2 dobras | 4 dobras | 6 dobras (Prazo mínimo - 7 dias úteis)</label>
                            </div>
                                
                            <div class="inputCheckbox">
                                <input name="outros" type="checkbox" class="box" id="outros" onchange="habilitarCheckOutros()">
                                <label class="label" for="sugestao">Outro:</label>
                                <input name="checkOutros" type="text" class="checkOutros" id="checkOutros" placeholder="Sua resposta" disabled>
                            </div>

                        </div>

<!---------------------------------- Select de outros serviços --------------------------------------->

                        <div class="inputbox">

                            <h1 class="title">OUTROS SERVIÇOS:</h1>

                            <div class="inputCheckbox">
                                <input name="foto" type="checkbox" class="box" id="foto" placeholder="">
                                <label class="label" for="foto">Foto</label>
                            </div>

                            <div class="inputCheckbox">
                                <input name="video" type="checkbox" class="box" id="video" placeholder="">
                                <label class="label" for="video">Vídeo | Animação Gráfica</label>
                            </div>
                           
                            <div class="inputCheckbox">
                                <input name="brindes" type="checkbox" class="box" id="brindes" placeholder="">
                                <label class="label" for="brindes">Brindes corporativos</label>
                            </div>

                            <div class="inputCheckbox">
                                <input name="outrosBox" type="checkbox" class="box" id="outrosBox" onchange="habilitarCheckOutrosServ()" placeholder="">
                                <label class="label" for="outrosBox">Outros</label>
                                <input name="checkOutrosServ" type="text" class="checkOutrosServ" id="checkOutrosServ" placeholder="Sua resposta" disabled>
                            </div>

                        </div>

<!---------------------------------- Campo de formulário (Materiais) --------------------------------->

                        <div class="inputbox">
                            <label class="title" for="materiais">MEDIDAS PARA MATERIAIS IMPRESSOS:</label>
                            <input name="materiais" type="text" class="inputUser" id="materiais" placeholder="Sua resposta">
                        </div>
                        <a class="back" href="index.html">Voltar</a>
                        <input class="submit" type="submit" value="Enviar" name="submit">
                        <input class="resetForm" type="reset" value="Limpar Formulário">
                    </form>
            </div>
    </section>

    <a href="#" class="btn"></a>

<!---------------------------------- js para habilitar campo de outros  ------------------------------>
    <script>
        function habilitarCheckOutros() {
            var checkboxOutros = document.getElementById('outros');
            var inputCheckOutros = document.getElementById('checkOutros');

            // Se o checkbox "outros" estiver marcado, habilita o input
            inputCheckOutros.disabled = !checkboxOutros.checked;
        }
    </script>
    <script>
        function habilitarCheckOutrosServ() {
            var checkboxOutrosServ = document.getElementById('outrosBox');
            var inputCheckOutrosServ = document.getElementById('checkOutrosServ');

            // Se o checkbox "outrosServ" estiver marcado, habilita o input
            inputCheckOutrosServ.disabled = !checkboxOutrosServ.checked;
        }
    </script>

<!---------------------------------- js para adicionar arquivos  ------------------------------------->
    <script>
        $("#referencia").change(function(){
            readURL(this);
        });

        function readURL(input) {
            if (input.files && input.files[0]) {
                if(input.files.length > 1){
                    for (let i = 0; i < input.files.length; i++){
                        $('#nome').append(input.files[i].name + ' <br/> ');
                    }
                }else{
                    $('#nome').html('');
                }
            }
        }
    </script>
</body>