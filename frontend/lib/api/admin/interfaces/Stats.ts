export default interface IStatsGlobal {
  total: {
    interactions: {
      passive: number;
      active: number;
    };
    structures: number;
    membranes: number;
    methods: number;
  };
  plots: {
    interactionsLine?: ILineChartSetting;
    databasesBar?: IBarChartSetting;
    proteinsBar?: IBarChartSetting;
  };
}

export interface ILineChartDataItem {
  date: number;
  value1: number;
  value2?: number;
}

export interface ILineChartSetting {
  data: ILineChartDataItem[];
}

export interface IBarChartSetting {
  items: {
    name: string;
    value1: number;
    value2?: number;
  }[];
}
