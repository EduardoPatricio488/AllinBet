<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use App\Models\GameRound;
use App\Services\ProvablyFairService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FairnessModal extends Component
{
    public bool $open = false;

    /** @var array<string, mixed> */
    public array $verification = [];

    public function openRound(int $roundId): void
    {
        abort_unless(Auth::check(), 403);

        $round = GameRound::query()
            ->whereKey($roundId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($round->status->value !== 'completed') {
            return;
        }

        $serverSeed = $round->revealedServerSeed();
        $commitmentVerified = $serverSeed !== null
            && app(ProvablyFairService::class)->verifyCommitment($serverSeed, $round->server_seed_hash);

        $result = $round->publicResult();
        $game = $round->game->value;

        $this->verification = [
            'id' => $round->id,
            'game' => $game,
            'game_label' => str($game)->headline()->toString(),
            'bet' => (int) $round->bet,
            'payout' => (int) $round->payout,
            'net' => (int) $round->payout - (int) $round->bet,
            'created_at' => optional($round->created_at)->format('d/m/Y H:i:s'),
            'server_seed_hash' => $round->server_seed_hash,
            'client_seed' => $round->client_seed,
            'nonce' => (int) $round->nonce,
            'server_seed' => $serverSeed,
            'commitment_verified' => $commitmentVerified,
            'result' => $result,
            'mechanism' => $this->mechanism($game),
            'explanation' => $this->explanation($round, $result),
        ];

        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
    }

    /** @return array<string, string> */
    private function mechanism(string $game): array
    {
        if ($game === 'blackjack') {
            return [
                'title' => 'Atenção: Blackjack usa um baralho aleatório separado',
                'status' => 'O Blackjack atual não deriva as cartas através do ProvablyFairService.',
                'detail' => 'A ronda guarda um compromisso SHA-256 do seed do servidor, mas o baralho é baralhado com random_int(). Por isso, o resultado das cartas não pode ser reproduzido apenas com o seed do servidor, seed do cliente e nonce. A verificação abaixo confirma o compromisso do seed, mas não prova matematicamente a sequência das cartas desta implementação.',
            ];
        }

        return [
            'title' => 'Resultado derivado de forma determinística',
            'status' => 'O resultado do jogo é calculado a partir dos dados da ronda e do mecanismo provably fair.',
            'detail' => 'Depois de a ronda ser preparada, o servidor publica primeiro apenas o hash do seed. O seed original permanece secreto até a ronda terminar. O resultado é então derivado com HMAC-SHA-256 usando o seed do servidor, o seed do cliente e o nonce.',
        ];
    }

    /** @return list<string> */
    private function explanation(GameRound $round, array $result): array
    {
        $game = $round->game->value;
        $bet = (int) $round->bet;
        $payout = (int) $round->payout;
        $net = $payout - $bet;

        $lines = [
            "A ronda #{$round->id} foi preparada com uma aposta de {$bet} créditos virtuais.",
            "Antes de revelar o resultado, o servidor fixou o compromisso através de SHA-256 do seed secreto. O hash publicado não permite obter o seed original, mas permite verificar mais tarde se o servidor mudou esse seed.",
            "O seed do cliente "{$round->client_seed}" e o nonce {$round->nonce} fazem parte da entrada usada para derivar o resultado. O nonce identifica a posição desta ronda na sequência do mesmo jogo para esse utilizador.",
        ];

        if ($game === 'slots') {
            $lines[] = 'Nas Slots, cada uma das 9 posições da grelha é calculada separadamente. O nonce usado em cada posição é: (nonce × 9) + índice da posição. Depois, os valores são convertidos para os símbolos disponíveis.';
            $lines[] = 'Só existem prémios quando os três símbolos de uma linha horizontal ou vertical são iguais. Pares ou linhas incompletas não pagam.';
        } elseif ($game === 'dice') {
            $lines[] = 'Nos Dados, o mecanismo transforma o valor determinístico num inteiro entre 0 e 9999. Esse valor é apresentado como uma casa decimal de 0,00 a 99,99 e é comparado com o limite e a direção escolhidos.';
        } elseif ($game === 'roulette') {
            $lines[] = 'Na Roleta, o mecanismo transforma o valor determinístico num inteiro entre 0 e 36. Esse número define a casa que saiu; depois são avaliados a cor, paridade, intervalo, dúzia ou coluna escolhidos.';
        } elseif ($game === 'coinflip') {
            $lines[] = 'No Coinflip, o mecanismo transforma o valor determinístico num inteiro entre 0 e 1. 0 corresponde a Cara e 1 corresponde a Coroa; depois é comparado com o lado escolhido.';
        } elseif ($game === 'blackjack') {
            $lines[] = 'No Blackjack, as cartas são atualmente geradas por um baralho baralhado com random_int(). A decisão da mão é feita pelas regras do jogo: pontuação do jogador, pontuação do dealer e ações realizadas.';
        }

        $lines[] = $net >= 0
            ? "No final, a ronda creditou {$payout} créditos. Como a aposta inicial foi {$bet}, a variação líquida desta ronda foi +{$net} créditos."
            : "No final, a ronda creditou {$payout} créditos. Como a aposta inicial foi {$bet}, a variação líquida desta ronda foi {$net} créditos.";

        return $lines;
    }

    public function render()
    {
        return view('livewire.casino.fairness-modal');
    }
}
