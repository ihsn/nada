import {
  CategoryScale,
  Chart,
  Filler,
  Legend,
  LinearScale,
  LineController,
  LineElement,
  PointElement,
  Tooltip,
} from 'chart.js';
import { onBeforeUnmount, ref } from 'vue';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Legend, Tooltip, Filler);

export function useLineChart() {
  const canvasRef = ref(null);
  let chart = null;

  function destroy() {
    if (chart) {
      chart.destroy();
      chart = null;
    }
  }

  function render(config) {
    const el = canvasRef.value;
    if (!el) return;
    destroy();
    chart = new Chart(el, config);
  }

  onBeforeUnmount(destroy);

  return { canvasRef, render, destroy };
}
