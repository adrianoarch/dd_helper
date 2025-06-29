# DD Helper - Advanced Debug Helper for CodeIgniter 2/3

![MIT License](https://img.shields.io/badge/license-MIT-blue.svg)

Um helper de debug avançado para CodeIgniter 2 e 3, inspirado no `dd()` do Laravel. Fornece funções para debug visual, seguro e prático com recursos avançados para logs, tempo, queries, memória, sessão, request e mais.

## ✨ Recursos

- **Debug visual interativo** para variáveis, arrays e objetos
- **Logs detalhados** com contexto e localização
- **Benchmark de performance** com múltiplos timers
- **Monitoramento de memória** com formatação automática
- **Inspeção de queries SQL** executadas
- **Análise de sessão** por chave ou completo
- **Detalhes de requisição** HTTP
- **Stack trace** navegável
- **Exceções detalhadas** com highlight
- **Comparação de variáveis** com diff
- **Saída em JSON** formatada
- **Proteção automática** (só funciona em ambiente development)

## 📦 Instalação

1. Coloque o arquivo `dd_helper.php` no diretório `application/helpers`
2. Carregue o helper onde for usar:
```php
$this->load->helper('dd_helper');
```
Ou no `autoload.php`:
```php
$autoload['helper'] = array(..., 'dd_helper');
```

## 🚀 Utilização Básica

### Debug e Die (dd)
```php
dd($variavel, $outra_variavel);
```

### Debug sem parar execução (d)
```php
d($array, $objeto);
```

### Debug em JSON
```php
ddJson($dados);
```

## 🔍 Funcionalidades Detalhadas

### 📝 Logs Estruturados
```php
ddLog('Mensagem importante', ['contexto' => 'valor']);
```
- Grava em `application/logs/debug_YYYY-MM-DD.log`
- Formato JSON com timestamp, arquivo, linha e contexto

### ⏱️ Benchmark com Timer
```php
ddTimer('processo'); // Inicia timer
// ... código ...
echo ddTimer('processo'); // Finaliza e exibe tempo
```
- Suporte a múltiplos timers simultâneos
- Saída em milissegundos

### 🗃️ Inspeção de Queries SQL
```php
ddQuery();          // Todas as queries
ddQuery(false);     // Apenas última query
```
- Mostra última query executada
- Lista todas as queries do request
- Contagem total de queries

### 💾 Monitor de Memória
```php
ddMemory();                 // Uso atual
ddMemory('pós-processo');   // Com rótulo
```
- Memória atual usada
- Pico de memória
- Limite configurado

### 🔐 Inspeção de Sessão
```php
ddSession();        // Todos os dados
ddSession('chave'); // Valor específico
```

### 🌐 Detalhes de Request
```php
ddRequest();
```
- Método HTTP
- URI
- Parâmetros GET/POST
- Headers
- User Agent
- IP

### 🧣 Stack Trace
```php
ddTrace(5); // Últimos 5 passos
```

### 🚨 Debug de Exceções
```php
try {
    // código
} catch (Exception $e) {
    ddException($e);
}
```
- Visualização detalhada
- Stack trace navegável
- Botão para copiar detalhes

### 🔄 Comparação de Variáveis
```php
ddCompare($original, $modificado, 'Original', 'Modificado');
```
- Verificação de igualdade (=== e ==)
- Diff entre valores
- Rótulos customizáveis

## ⚠️ Boas Práticas

1. Use apenas em ambiente de desenvolvimento:
```php
define('ENVIRONMENT', 'development');
```
2. Remova ou desative em produção
3. Não deixe chamadas de debug em código commitado
4. Use `ddLog` para registro persistente de eventos

## 🖥️ Exemplo de Saída

![Exemplo de saída do dd()](https://via.placeholder.com/800x400/1e1e1e/50fa7b?text=Debug+Visual+Interativo)
*(Visualização interativa com syntax highlighting e colapsável)*

## 📄 Licença

MIT License - Livre para uso e modificação.