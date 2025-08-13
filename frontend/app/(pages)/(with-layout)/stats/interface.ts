/**
 * Interface describing remote data structure
 */
export interface Props {}

/**
 * Parses the given data and returns a Props object or null.
 *
 * @param {any} data - The data to be parsed.
 * @return {Props | null} The parsed Props object or null if data is falsy.
 */
function parse(data: any): Props | null {
  if (!data || !data?.data) return null;
  // Extract only local data
  data = data?.data;

  var result: Props = {};

  return result;
}

function is_instance(obj: any, class_name: string): boolean {
  return obj instanceof Object && obj._instance == class_name;
}

export default parse;
